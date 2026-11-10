@use('App\Http\Controllers\Shelter\WalkBookingController')
@use('App\Support\LocalTime')
{{-- Записи на выгул: ждут подтверждения → предстоящие → отметить после прогулки → архив. --}}
<x-layouts.cabinet title="Записи на выгул">
    <x-slot:actions>
        <x-button variant="secondary" icon="calendar" :href="route('shelter.slots.index')">Слоты выгула</x-button>
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-6">
        @if ($errors->hasAny(['status', 'shelter_comment']))
            <x-alert tone="danger">{{ $errors->first('status') ?: $errors->first('shelter_comment') }}</x-alert>
        @endif

        <x-tabs label="Записи на выгул" :items="collect(WalkBookingController::TABS)->map(fn ($label, $key) => [
            'label' => $label,
            'href' => route('shelter.bookings.index', ['tab' => $key]),
            'active' => $tab === $key,
            'count' => $counts[$key],
        ])->values()->all()" />

        @if ($bookings->isEmpty())
            <x-empty-state :title="match ($tab) {
                'pending' => 'Новых записей нет',
                'upcoming' => 'Подтверждённых прогулок пока нет',
                'to_mark' => 'Все прогулки отмечены',
                default => 'Архив пуст',
            }" illustration="dog-caramel">
                {{ match ($tab) {
                    'pending' => 'Когда волонтёр запишется на прогулку, запись появится здесь — подтвердите её или отклоните.',
                    'to_mark' => 'После прогулки отметьте, кто пришёл, — так приют видит надёжных волонтёров.',
                    default => 'Записи появятся, когда волонтёры начнут записываться на ваши слоты.',
                } }}
                <x-slot:action><x-button variant="secondary" icon="calendar" :href="route('shelter.slots.index')">К слотам</x-button></x-slot:action>
            </x-empty-state>
        @else
            <x-table caption="Записи на выгул">
                <x-slot:head>
                    <tr>
                        <x-table.th>Когда</x-table.th>
                        <x-table.th>Волонтёр</x-table.th>
                        <x-table.th>Собака</x-table.th>
                        <x-table.th>Комментарий</x-table.th>
                        <x-table.th>Статус</x-table.th>
                        <x-table.th><span class="sr-only">Действия</span></x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($bookings as $booking)
                    <tr>
                        <td class="min-w-40">
                            <a href="{{ route('shelter.slots.show', $booking->slot) }}" class="font-semibold hover:underline">
                                <time datetime="{{ LocalTime::iso($booking->slot->starts_at) }}">{{ Str::ucfirst(LocalTime::weekdayShort($booking->slot->starts_at)) }}, {{ LocalTime::date($booking->slot->starts_at) }}</time>
                            </a>
                            <p class="text-sm text-secondary tabular-nums">{{ LocalTime::timeRange($booking->slot->starts_at, $booking->slot->ends_at) }}</p>
                        </td>
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
            <x-pagination :paginator="$bookings" />
        @endif
    </div>
</x-layouts.cabinet>
