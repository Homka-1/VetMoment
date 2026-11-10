@use('App\Http\Controllers\Cabinet\WalkController')
@use('App\Support\LocalTime')
@use('App\Support\Plural')
{{-- «Мои выгулы»: предстоящие и прошедшие записи; лимит активных записей и предупреждение о неявках. --}}
<x-layouts.cabinet title="Мои выгулы">
    <x-slot:actions>
        <x-button icon="leash" :href="route('walks.index')">Записаться на выгул</x-button>
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-6">
        @if ($manyNoShows)
            <x-alert tone="warning" title="Вы пропустили несколько прогулок">
                Приюты видят это в записях. Если планы меняются — отменяйте запись заранее, чтобы место досталось другому волонтёру.
            </x-alert>
        @endif

        <x-tabs label="Мои выгулы" :items="collect(WalkController::TABS)->map(fn ($label, $key) => [
            'label' => $label,
            'href' => route('cabinet.walks', ['tab' => $key]),
            'active' => $tab === $key,
            'count' => $counts[$key],
        ])->values()->all()" />

        @if ($tab === 'upcoming' && $bookings->isNotEmpty())
            <p class="text-sm text-secondary">
                Активных записей: {{ $activeCount }} из {{ $limit }}. Отменить запись можно не позже чем за
                {{ Plural::format((int) config('walks.cancel_before_hours', 3), ['час', 'часа', 'часов']) }} до начала.
            </p>
        @endif

        @if ($bookings->isEmpty())
            <x-empty-state :title="$tab === 'upcoming' ? 'Предстоящих прогулок нет' : 'Вы ещё не гуляли с собаками приютов'" illustration="dog-caramel">
                Выберите приют и свободное время — прогулка займёт около часа, а собаке она нужна каждый день.
                <x-slot:action><x-button icon="leash" :href="route('walks.index')">Выбрать время</x-button></x-slot:action>
            </x-empty-state>
        @else
            <x-table caption="Мои выгулы">
                <x-slot:head>
                    <tr>
                        <x-table.th>Когда</x-table.th>
                        <x-table.th>Собака</x-table.th>
                        <x-table.th>Приют</x-table.th>
                        <x-table.th>Статус</x-table.th>
                        <x-table.th><span class="sr-only">Действия</span></x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($bookings as $booking)
                    <tr>
                        <td class="min-w-44">
                            <p class="font-semibold"><time datetime="{{ LocalTime::iso($booking->slot->starts_at) }}">{{ Str::ucfirst(LocalTime::weekdayShort($booking->slot->starts_at)) }}, {{ LocalTime::date($booking->slot->starts_at) }}</time></p>
                            <p class="text-sm text-secondary tabular-nums">{{ LocalTime::timeRange($booking->slot->starts_at, $booking->slot->ends_at) }}</p>
                        </td>
                        <td class="min-w-44">
                            <div class="flex items-center gap-3">
                                <x-pet-thumb :pet="$booking->pet" size="sm" class="rounded-pill!" />
                                <span class="font-semibold">{{ $booking->pet?->name ?? 'Любая собака' }}</span>
                            </div>
                        </td>
                        <td class="min-w-40">{{ $booking->slot->shelter->name }}<p class="text-xs text-secondary">{{ $booking->slot->shelter->city }}</p></td>
                        <td><x-badge :status="$booking->status" /></td>
                        <td class="text-right"><x-button variant="secondary" size="sm" :href="route('walks.bookings.show', $booking)">Открыть</x-button></td>
                    </tr>
                @endforeach
            </x-table>
            <x-pagination :paginator="$bookings" />
        @endif
    </div>
</x-layouts.cabinet>
