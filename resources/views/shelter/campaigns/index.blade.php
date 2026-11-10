@use('App\Enums\CampaignStatus')
@use('App\Support\LocalTime')
@use('App\Support\Money')
@use('App\Support\Plural')
{{-- Сборы приюта: фильтр по статусу, таблица (прогресс, срок, отчёты), закрытие и архив с подтверждением. --}}
<x-layouts.cabinet title="Сборы">
    <x-slot:actions>
        <x-button icon="plus" :href="route('shelter.campaigns.create')">Создать сбор</x-button>
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-6">
        <x-shelter-moderation :shelter="$shelter" />

        <ul class="flex flex-wrap gap-2" aria-label="Статус">
            @foreach ($tabs as $key => $item)
                <li>
                    <x-chip :href="route('shelter.campaigns.index', $key === 'all' ? [] : ['status' => $key])" :active="$tab === $key" :count="$item['count']">{{ $item['label'] }}</x-chip>
                </li>
            @endforeach
        </ul>

        @if ($campaigns->isEmpty())
            @if ($tab !== 'all')
                <x-empty-state title="Сборов с этим статусом нет" illustration="cat-smoke" tone="sky">
                    <x-slot:action><x-button variant="secondary" :href="route('shelter.campaigns.index')">Показать все</x-button></x-slot:action>
                </x-empty-state>
            @else
                <x-empty-state title="Запустите первый сбор" tone="tag">
                    Конкретная цель, смета и срок — например, «Операция на лапе для Ромашки, 600 000 ₸ до 15 ноября» — собирают быстрее, чем «на нужды приюта».
                    <x-slot:action><x-button icon="plus" :href="route('shelter.campaigns.create')">Создать сбор</x-button></x-slot:action>
                </x-empty-state>
            @endif
        @else
            <x-table caption="Сборы приюта">
                <x-slot:head>
                    <tr>
                        <x-table.th>Сбор</x-table.th>
                        <x-table.th>Статус</x-table.th>
                        <x-table.th>Собрано</x-table.th>
                        <x-table.th>Срок</x-table.th>
                        <x-table.th align="right">Отчёты</x-table.th>
                        <x-table.th><span class="sr-only">Действия</span></x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($campaigns as $campaign)
                    @php($closable = in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Completed], true))
                    <tr>
                        <td class="min-w-64">
                            <a href="{{ route('shelter.campaigns.edit', $campaign) }}" class="font-semibold text-primary hover:underline">{{ $campaign->title }}</a>
                            <p class="text-xs text-secondary">{{ $campaign->pet ? 'На питомца: '.$campaign->pet->name : 'Нужды приюта' }}</p>
                        </td>
                        <td><x-badge :status="$campaign->status" /></td>
                        <td class="min-w-52">
                            <p class="text-sm font-semibold whitespace-nowrap tabular-nums">{{ Money::format($campaign->raised_amount) }} <span class="font-normal text-secondary">из {{ Money::format($campaign->goal_amount) }}</span></p>
                            <x-progress :raised="$campaign->raised_amount" :goal="$campaign->goal_amount" compact class="mt-2" />
                            <p class="mt-1 text-xs text-secondary">{{ Plural::format((int) $campaign->donations_count, ['человек', 'человека', 'человек']) }}</p>
                        </td>
                        <td class="whitespace-nowrap">
                            @if ($campaign->ends_at)
                                <time datetime="{{ LocalTime::iso($campaign->ends_at) }}">до {{ LocalTime::date($campaign->ends_at) }}</time>
                                @if ($campaign->status === CampaignStatus::Active && $campaign->isExpired())
                                    <p class="text-xs text-danger-fg">срок прошёл</p>
                                @endif
                            @else
                                <span class="text-muted">без срока</span>
                            @endif
                        </td>
                        <td class="text-right tabular-nums">
                            <a href="{{ route('shelter.campaigns.reports.index', $campaign) }}" class="link font-semibold">{{ $campaign->reports_count ?: 'Добавить' }}</a>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                @if (in_array($campaign->status, CampaignStatus::public(), true))
                                    <x-button variant="ghost" size="sm" icon="tenge" icon-only :href="route('campaigns.show', $campaign)" :label="'Открыть сбор «'.$campaign->title.'» на сайте'" />
                                @endif
                                <x-button variant="ghost" size="sm" icon="edit" icon-only :href="route('shelter.campaigns.edit', $campaign)" :label="'Редактировать «'.$campaign->title.'»'" />
                                @if ($closable)
                                    <x-button variant="ghost" size="sm" icon="x" icon-only type="button" data-modal-open="close-{{ $campaign->id }}" :label="'Закрыть сбор «'.$campaign->title.'»'" />
                                @elseif ($campaign->status !== CampaignStatus::Archived)
                                    <x-button variant="ghost" size="sm" icon="trash" icon-only type="button" data-modal-open="archive-{{ $campaign->id }}" :label="'Перенести «'.$campaign->title.'» в архив'" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            @foreach ($campaigns as $campaign)
                @include('shelter.campaigns.partials.modals', ['campaign' => $campaign])
            @endforeach

            <x-pagination :paginator="$campaigns" />
        @endif
    </div>
</x-layouts.cabinet>
