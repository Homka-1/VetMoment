@use('App\Enums\BookingStatus')
@use('App\Support\LocalTime')
@use('App\Support\Phone')
@use('App\Support\WalkWeek')
{{-- Запись на выгул (макет 06-success): статус, собака, время и место, «Добавить в календарь», «Мои выгулы», отмена.
     Отменить на сайте можно не позже чем за N часов, дальше — звонок в приют. --}}
@php
    $status = $booking->status;
    $future = $slot->starts_at->isFuture();
    $place = $slot->meeting_point ?: trim($shelter->city.', '.$shelter->address, ', ');
    $againUrl = route('walks.index', ['shelter' => $shelter->slug, 'week' => WalkWeek::mondayOf(now())->format('Y-m-d')]);

    $view = match ($status) {
        BookingStatus::Pending, BookingStatus::Confirmed => ['icon' => 'leash', 'circle' => 'bg-meadow-100 text-meadow-700', 'title' => 'Вы записаны на выгул'],
        BookingStatus::Completed => ['icon' => 'check-circle', 'circle' => 'bg-meadow-100 text-meadow-700', 'title' => 'Прогулка состоялась'],
        BookingStatus::Rejected => ['icon' => 'x', 'circle' => 'bg-danger-bg text-danger-fg', 'title' => 'Приют отклонил запись'],
        BookingStatus::Cancelled => ['icon' => 'x', 'circle' => 'bg-ink-100 text-secondary', 'title' => 'Запись отменена'],
        BookingStatus::NoShow => ['icon' => 'clock', 'circle' => 'bg-ink-100 text-secondary', 'title' => 'Прогулка пропущена'],
    };
    if ($status->isActive() && ! $future) {
        $view['title'] = 'Прогулка прошла';
    }
@endphp
<x-layouts.app :title="$view['title']" canvas>
    <x-slot:head><meta name="robots" content="noindex"></x-slot:head>

    <div class="page-container py-10 lg:py-16">
        <section class="mx-auto grid max-w-[640px] gap-6 rounded-lg border border-subtle bg-raised px-5 py-8 sm:px-10 sm:py-10" aria-labelledby="booking-title">
            <div class="grid justify-items-center gap-4 text-center">
                <span @class(['grid size-24 place-items-center rounded-pill', $view['circle']])><x-icon :name="$view['icon']" size="lg" class="size-10" /></span>
                <h1 id="booking-title" class="type-h2">{{ $view['title'] }}</h1>
                <x-badge :status="$status" />
            </div>

            <div class="grid gap-4 rounded-lg bg-canvas p-5 sm:p-6">
                <div class="flex items-center gap-4">
                    <x-pet-thumb :pet="$booking->pet" size="lg" />
                    <div class="min-w-0">
                        <p class="type-h4 font-semibold">
                            @if ($booking->pet && Gate::allows('view', $booking->pet))
                                <a href="{{ route('pets.show', $booking->pet) }}" class="hover:underline">{{ $booking->pet->name }}</a>
                            @else
                                {{ $booking->pet?->name ?? 'Собаку подберёт приют' }}
                            @endif
                        </p>
                        <p class="text-secondary">{{ $shelter->name }}, {{ $shelter->city }}</p>
                    </div>
                </div>
                <ul class="grid gap-3 text-secondary">
                    <li class="flex items-start gap-3"><x-icon name="calendar" class="text-ink-500" />
                        <time datetime="{{ LocalTime::iso($slot->starts_at) }}">{{ Str::ucfirst(LocalTime::dayLong($slot->starts_at)) }}, {{ LocalTime::timeRange($slot->starts_at, $slot->ends_at) }}</time>
                    </li>
                    @if ($place)
                        <li class="flex items-start gap-3"><x-icon name="map-pin" class="text-ink-500" />{{ $place }}</li>
                    @endif
                    @if ($shelter->phone)
                        <li class="flex items-start gap-3"><x-icon name="phone" class="text-ink-500" />
                            <a href="tel:{{ $shelter->phone }}" class="hover:underline">{{ Phone::format($shelter->phone) }}</a>
                        </li>
                    @endif
                </ul>
                @if ($booking->comment)
                    <p class="border-t border-subtle pt-4 text-sm text-secondary"><span class="font-semibold text-primary">Ваш комментарий:</span> {{ $booking->comment }}</p>
                @endif
            </div>

            <div class="grid gap-2 text-secondary">
                @if ($status->isActive() && $future)
                    <p>
                        {{ $status === BookingStatus::Pending ? 'Приют посмотрит запись и подтвердит её — статус обновится здесь и в «Мои выгулы».' : 'Приют подтвердил запись и ждёт вас.' }}
                        @if ($canCancel)
                            Если планы изменятся, отмените запись не позже чем за {{ $cancelHours }} до начала.
                        @else
                            Отменить запись на сайте уже нельзя — до начала меньше {{ $cancelHours }}. Если не сможете прийти, позвоните в приют.
                        @endif
                    </p>
                @elseif (in_array($status, [BookingStatus::Rejected, BookingStatus::Cancelled], true) && $booking->shelter_comment)
                    <p><span class="font-semibold text-primary">Причина:</span> {{ $booking->shelter_comment }}</p>
                @elseif ($status === BookingStatus::Completed)
                    <p>Спасибо, что погуляли! Собаки, которые часто гуляют, спокойнее и быстрее находят дом.</p>
                @endif
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @if ($status->isActive() && $future)
                    <x-button variant="secondary" size="lg" icon="calendar" :href="route('walks.bookings.calendar', $booking)" block>Добавить в календарь</x-button>
                    <x-button size="lg" :href="route('cabinet.walks')" block>Мои выгулы</x-button>
                @else
                    <x-button size="lg" icon="leash" :href="$againUrl" block>Выбрать новое время</x-button>
                    <x-button variant="secondary" size="lg" :href="route('cabinet.walks')" block>Мои выгулы</x-button>
                @endif
            </div>

            @if ($canCancel)
                <div class="text-center">
                    <button type="button" data-modal-open="cancel-booking" class="font-semibold text-danger-fg underline-offset-3 hover:underline">Отменить запись</button>
                </div>
                <x-modal id="cancel-booking" title="Отменить запись?">
                    Место освободится, и на прогулку сможет записаться другой волонтёр.
                    <x-slot:footer>
                        <x-button variant="ghost" type="button" data-modal-close>Не отменять</x-button>
                        <form method="POST" action="{{ route('walks.bookings.cancel', $booking) }}">
                            @csrf
                            <x-button variant="danger" block>Отменить запись</x-button>
                        </form>
                    </x-slot:footer>
                </x-modal>
            @endif
        </section>
    </div>
</x-layouts.app>
