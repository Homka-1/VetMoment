@use('App\Http\Controllers\Admin\CampaignController')
@use('App\Support\LocalTime')
@use('App\Support\Money')
{{-- Сборы: модерация. «Скрыть» убирает сбор с сайта — пожертвования на него не принимаются. --}}
<x-layouts.cabinet title="Сборы">
    <div class="grid grid-cols-1 gap-6">
        <x-tabs label="Сборы" :items="collect(CampaignController::TABS)->map(fn ($label, $key) => [
            'label' => $label,
            'href' => route('admin.campaigns.index', ['tab' => $key]),
            'active' => $tab === $key,
            'count' => $counts[$key],
        ])->values()->all()" />

        @if ($campaigns->isEmpty())
            <x-empty-state :title="$tab === 'hidden' ? 'Скрытых сборов нет' : 'Сборов пока нет'" illustration="dog-caramel" tone="tag">
                Сборы, которые вы скроете, появятся здесь — их можно вернуть на сайт.
            </x-empty-state>
        @else
            <x-table caption="Сборы">
                <x-slot:head>
                    <tr>
                        <x-table.th>Сбор</x-table.th>
                        <x-table.th>Приют</x-table.th>
                        <x-table.th align="right">Собрано</x-table.th>
                        <x-table.th>Статус</x-table.th>
                        <x-table.th><span class="sr-only">Действия</span></x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($campaigns as $campaign)
                    <tr>
                        <td class="min-w-56 max-w-80"><a href="{{ route('campaigns.show', $campaign) }}" class="link font-semibold">{{ $campaign->title }}</a></td>
                        <td class="min-w-40">{{ $campaign->shelter->name }}<p class="text-xs text-secondary">{{ $campaign->shelter->city }}</p></td>
                        <td class="text-right whitespace-nowrap tabular-nums">{{ Money::format($campaign->raised_amount) }}<p class="text-xs text-secondary">из {{ Money::format($campaign->goal_amount) }}</p></td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <x-badge :status="$campaign->status" />
                                @if ($campaign->is_hidden)<x-badge tone="danger">Скрыт с сайта</x-badge>@endif
                            </div>
                        </td>
                        <td class="text-right">
                            @if ($campaign->is_hidden)
                                <form method="POST" action="{{ route('admin.campaigns.restore', $campaign) }}">@csrf<x-button variant="secondary" size="sm">Вернуть</x-button></form>
                            @else
                                <form method="POST" action="{{ route('admin.campaigns.hide', $campaign) }}">@csrf<x-button variant="secondary" size="sm">Скрыть</x-button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
            <x-pagination :paginator="$campaigns" />
        @endif
    </div>
</x-layouts.cabinet>
