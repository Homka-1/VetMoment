@use('App\Enums\WalkSlotStatus')
@use('App\Support\LocalTime')
@use('App\Support\WalkWeek')
{{-- Слот выгула в кабинете приюта: записавшиеся с действиями, изменение слота, отмена слота целиком.
     С записями время не меняется и мест не меньше, чем записано (docs/product/user-flows.md, 4). --}}
@php
    $state = $slot->state();
    $title = Str::ucfirst(LocalTime::weekdayShort($slot->starts_at)).', '.LocalTime::date($slot->starts_at).', '.LocalTime::timeRange($slot->starts_at, $slot->ends_at);
    $active = $bookings->filter(fn ($b) => $b->status->isActive())->count();
@endphp
<x-layouts.cabinet title="Слот выгула" :heading="$title">
    <x-slot:actions>
        <x-button variant="secondary" icon="chevron-left" :href="route('shelter.slots.index', ['week' => WalkWeek::mondayOf($slot->starts_at)->format('Y-m-d')])">К неделе</x-button>
        @if ($editable)
            <x-button variant="danger" type="button" data-modal-open="cancel-slot">Отменить слот</x-button>
        @endif
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-8">
        <div class="grid min-w-0 content-start gap-6">
            <div class="flex flex-wrap items-center gap-3">
                <x-badge :status="$state" />
                <span class="text-secondary">Записано {{ $slot->booked_count }} из {{ $slot->capacity }}</span>
                @if ($slot->template)
                    <span class="text-sm text-muted">· из шаблона недели</span>
                @endif
            </div>

            @if ($errors->hasAny(['status', 'shelter_comment']))
                <x-alert tone="danger">{{ $errors->first('status') ?: $errors->first('shelter_comment') }}</x-alert>
            @endif

            <section aria-labelledby="bookings-title" class="grid grid-cols-1 gap-4">
                <h2 id="bookings-title" class="type-h4 font-semibold">Записи</h2>
                @if ($bookings->isEmpty())
                    <p class="rounded-md border border-dashed border-ink-300 p-6 text-center text-secondary">Пока никто не записался.</p>
                @else
                    <x-table caption="Записи на слот">
                        <x-slot:head>
                            <tr>
                                <x-table.th>Волонтёр</x-table.th>
                                <x-table.th>Собака</x-table.th>
                                <x-table.th>Комментарий</x-table.th>
                                <x-table.th>Статус</x-table.th>
                                <x-table.th><span class="sr-only">Действия</span></x-table.th>
                            </tr>
                        </x-slot:head>
                        @foreach ($bookings as $booking)
                            <tr>
                                <td class="min-w-44">@include('shelter.bookings.partials.person')</td>
                                <td class="whitespace-nowrap">{{ $booking->pet?->name ?? 'на выбор приюта' }}</td>
                                <td class="max-w-56 min-w-32 text-sm text-secondary">{{ $booking->comment ?? '—' }}
                                    @if ($booking->shelter_comment)
                                        <p class="mt-1 text-xs">Причина: {{ $booking->shelter_comment }}</p>
                                    @endif
                                </td>
                                <td><x-badge :status="$booking->status" /></td>
                                <td class="w-px">@include('shelter.bookings.partials.actions', ['next' => $next[$booking->id] ?? []])</td>
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </section>
        </div>

        <x-card padding="lg" as="section" aria-labelledby="edit-slot-title">
            <h2 id="edit-slot-title" class="type-h4 font-semibold">{{ $editable ? 'Изменить слот' : 'Слот' }}</h2>
            @if ($editable)
                <form method="POST" action="{{ route('shelter.slots.update', $slot) }}" class="mt-5 grid gap-5" novalidate>
                    @csrf @method('PATCH')
                    @include('shelter.slots.partials.fields', [
                        'wide' => true,
                        'values' => [
                            'date' => LocalTime::of($slot->starts_at)->format('Y-m-d'),
                            'start_time' => LocalTime::time($slot->starts_at),
                            'end_time' => LocalTime::time($slot->ends_at),
                            'capacity' => $slot->capacity,
                            'meeting_point' => $slot->meeting_point,
                            'pets' => $slot->pets->pluck('id')->all(),
                        ],
                    ])
                    @if ($active > 0)
                        <p class="text-sm text-secondary">На слот уже записались — время не меняется, а мест не может быть меньше {{ $active }}. Чтобы перенести прогулку, отмените слот и создайте новый.</p>
                    @endif
                    <div><x-button>Сохранить слот</x-button></div>
                </form>
            @else
                <dl class="mt-4 grid gap-3 text-sm">
                    <div><dt class="text-secondary">Место сбора</dt><dd class="font-semibold">{{ $slot->meeting_point ?: 'адрес приюта' }}</dd></div>
                    <div><dt class="text-secondary">Собаки</dt><dd class="font-semibold">{{ $slot->pets->pluck('name')->implode(', ') ?: 'на выбор приюта' }}</dd></div>
                </dl>
                <p class="mt-4 text-sm text-muted">
                    {{ $slot->status === WalkSlotStatus::Cancelled ? 'Слот отменён — записи отменены.' : 'Прогулка уже началась — слот не меняется. Отметьте, кто пришёл.' }}
                </p>
            @endif
        </x-card>
    </div>

    @if ($editable)
        <x-modal id="cancel-slot" title="Отменить слот?">
            <form method="POST" action="{{ route('shelter.slots.cancel', $slot) }}" id="cancel-slot-form" class="grid gap-4">
                @csrf
                <p>{{ $active > 0 ? 'Все записи на этот слот будут отменены, волонтёры увидят причину.' : 'Слот исчезнет из календаря волонтёров.' }}</p>
                <x-textarea name="reason" label="Причина" required :maxlength="500" rows="3" placeholder="Например: приют закрыт на карантин" />
            </form>
            <x-slot:footer>
                <x-button variant="ghost" type="button" data-modal-close>Не отменять</x-button>
                <x-button variant="danger" form="cancel-slot-form">Отменить слот</x-button>
            </x-slot:footer>
        </x-modal>
    @endif
</x-layouts.cabinet>
