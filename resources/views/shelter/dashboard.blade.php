@use('App\Support\LocalTime')
@use('App\Support\Money')
@use('App\Support\Plural')
{{-- Сводка кабинета приюта (макет 10-dashboard): цифры, записи на сегодня, новые заявки. --}}
<x-layouts.cabinet title="Сводка">
    <x-slot:actions>
        <x-button icon="plus" :href="route('shelter.pets.create')">Добавить питомца</x-button>
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-8">
        <div class="flex flex-wrap items-center gap-3">
            <p class="font-semibold">{{ $shelter->name }}<span class="font-normal text-secondary">, {{ $shelter->city }}</span></p>
            <x-badge :status="$shelter->status" :icon="$shelter->isApproved() ? 'shield-check' : null" />
        </div>

        <x-shelter-moderation :shelter="$shelter" />

        <section aria-label="Цифры" class="grid grid-cols-2 gap-3 md:gap-4 xl:grid-cols-4">
            <x-stat label="Ищут дом" :value="$lookingForHome" :hint="$onTreatment > 0 ? 'и '.$onTreatment.' на лечении' : null" />
            <x-stat label="Новые заявки" :value="$newApplications" :hint="$newApplications > 0 ? 'ответьте как можно скорее' : 'всё разобрано'" :hint-tone="$newApplications > 0 ? 'warning' : 'muted'" />
            <x-stat label="Выгулы сегодня" :value="$walksToday" :hint="$walksPending > 0 ? $walksPending.' ждут подтверждения' : null" :hint-tone="$walksPending > 0 ? 'warning' : 'muted'" />
            <x-stat label="Собрано за месяц" :value="Money::format($raisedMonth)" :hint="'по '.Plural::format($activeCampaigns, ['активному сбору', 'активным сборам', 'активным сборам'])" tone="donate" />
        </section>

        <section aria-labelledby="today-walks">
            <div class="flex items-baseline justify-between gap-4">
                <h2 id="today-walks" class="type-h4">Записи на сегодня, {{ LocalTime::date(now()) }}</h2>
                <a href="{{ route('shelter.bookings.index') }}" class="link inline-flex shrink-0 items-center gap-1 text-sm whitespace-nowrap">Все записи<x-icon name="chevron-right" size="sm" /></a>
            </div>
            @if ($todayBookings->isEmpty())
                <p class="mt-4 text-secondary">На сегодня записей нет. Чтобы волонтёры могли записаться, добавьте слоты выгула.</p>
                <x-button class="mt-4" variant="secondary" size="sm" icon="calendar" :href="route('shelter.slots.index')">Слоты выгула</x-button>
            @else
                <x-table class="mt-4" caption="Записи на сегодня">
                    <x-slot:head>
                        <tr>
                            <x-table.th>Время</x-table.th>
                            <x-table.th>Волонтёр</x-table.th>
                            <x-table.th>Питомец</x-table.th>
                            <x-table.th>Статус</x-table.th>
                            <x-table.th><span class="sr-only">Действия</span></x-table.th>
                        </tr>
                    </x-slot:head>
                    @foreach ($todayBookings as $booking)
                        <tr>
                            <td class="min-w-32 whitespace-nowrap">
                                <p class="font-semibold tabular-nums">{{ LocalTime::timeRange($booking->slot->starts_at, $booking->slot->ends_at) }}</p>
                            </td>
                            <td class="min-w-48">@include('shelter.bookings.partials.person', ['booking' => $booking, 'noShows' => $noShows])</td>
                            <td>{{ $booking->pet?->name ?? 'Любая собака' }}</td>
                            <td><x-badge :status="$booking->status" /></td>
                            <td>@include('shelter.bookings.partials.actions', ['booking' => $booking, 'next' => $next[$booking->id]])</td>
                        </tr>
                    @endforeach
                </x-table>
            @endif
        </section>

        <section aria-labelledby="new-applications">
            <div class="flex items-baseline justify-between gap-4">
                <h2 id="new-applications" class="type-h4">Новые заявки на питомцев</h2>
                <a href="{{ route('shelter.applications.index') }}" class="link inline-flex shrink-0 items-center gap-1 text-sm whitespace-nowrap">Все заявки<x-icon name="chevron-right" size="sm" /></a>
            </div>
            @if ($applications->isEmpty())
                <p class="mt-4 text-secondary">Новых заявок нет. Подробные анкеты с несколькими фото получают их чаще.</p>
            @else
                <x-table class="mt-4" caption="Новые заявки">
                    <x-slot:head>
                        <tr>
                            <x-table.th>Питомец</x-table.th>
                            <x-table.th>Кто и что</x-table.th>
                            <x-table.th>Получена</x-table.th>
                            <x-table.th><span class="sr-only">Действия</span></x-table.th>
                        </tr>
                    </x-slot:head>
                    @foreach ($applications as $application)
                        <tr>
                            <td class="min-w-44">
                                <div class="flex items-center gap-3">
                                    <x-pet-thumb :pet="$application->pet" size="sm" class="rounded-pill!" />
                                    <span class="font-semibold">{{ $application->pet->name }}</span>
                                </div>
                            </td>
                            <td class="min-w-48">
                                <p class="font-semibold">{{ $application->contact_name }}</p>
                                <p class="text-xs text-secondary">{{ $application->type->label() }}</p>
                            </td>
                            <td class="whitespace-nowrap">{{ LocalTime::dateTime($application->created_at) }}</td>
                            <td class="text-right"><x-button variant="secondary" size="sm" :href="route('shelter.applications.show', $application)">Открыть</x-button></td>
                        </tr>
                    @endforeach
                </x-table>
            @endif
        </section>
    </div>
</x-layouts.cabinet>
