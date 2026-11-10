@use('App\Enums\PaymentStatus')
@use('App\Support\LocalTime')
@use('App\Support\Money')
@use('App\Support\Plural')
{{-- Пожертвования приюта: фильтры (сбор / общий фонд, статус, период), итог оплаченных, экспорт CSV. --}}
@php
    $campaignOptions = ['fund' => 'Общий фонд приюта'] + $campaigns;
    $active = array_filter($filters);
@endphp
<x-layouts.cabinet title="Пожертвования">
    <x-slot:actions>
        <x-button variant="secondary" :href="route('shelter.donations.export', $active)">Скачать CSV</x-button>
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-6">
        <form method="GET" action="{{ route('shelter.donations.index') }}" data-autosubmit aria-label="Фильтры пожертвований"
              class="grid gap-4 rounded-lg border border-subtle bg-raised p-4 sm:grid-cols-2 xl:grid-cols-[2fr_1fr_1fr_1fr_auto] xl:items-end">
            <x-select name="campaign" label="Назначение" :options="$campaignOptions" :value="$filters['campaign']" placeholder="Все" />
            <x-select name="status" label="Статус" :options="PaymentStatus::options()" :value="$filters['status']" placeholder="Любой" />
            <x-input name="from" type="date" label="С" :value="$filters['from']" :old-input="false" />
            <x-input name="to" type="date" label="По" :value="$filters['to']" :old-input="false" />
            <div class="flex gap-2">
                <x-button size="sm" variant="secondary" data-autosubmit-fallback>Показать</x-button>
                @if ($active)
                    <x-button size="sm" variant="ghost" :href="route('shelter.donations.index')">Сбросить</x-button>
                @endif
            </div>
        </form>

        <x-card class="flex flex-wrap items-baseline justify-between gap-3">
            <p class="text-secondary">
                Оплачено{{ $active ? ' по фильтру' : '' }}: {{ Plural::format($paidCount, ['пожертвование', 'пожертвования', 'пожертвований']) }}
            </p>
            <p class="type-number">{{ Money::format($total) }}</p>
        </x-card>

        @if ($donations->isEmpty())
            <x-empty-state :title="$active ? 'По этим фильтрам ничего нет' : 'Пожертвований пока нет'" illustration="cat-smoke" tone="tag">
                @if ($active)
                    Измените период или назначение.
                @else
                    Запустите сбор с понятной целью и сметой — так люди охотнее помогают.
                    <x-slot:action><x-button icon="plus" :href="route('shelter.campaigns.create')">Создать сбор</x-button></x-slot:action>
                @endif
            </x-empty-state>
        @else
            <x-table caption="Пожертвования приюта">
                <x-slot:head>
                    <tr>
                        <x-table.th>Дата</x-table.th>
                        <x-table.th>Жертвователь</x-table.th>
                        <x-table.th>Назначение</x-table.th>
                        <x-table.th align="right">Сумма</x-table.th>
                        <x-table.th>Статус</x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($donations as $donation)
                    <tr>
                        <td class="whitespace-nowrap"><time datetime="{{ LocalTime::iso($donation->created_at) }}">{{ LocalTime::dateTime($donation->created_at) }}</time></td>
                        <td class="min-w-56">
                            <p class="font-semibold">
                                {{ $donation->donor_name ?: 'Без имени' }}
                                @if ($donation->is_anonymous)
                                    <span class="ml-1 rounded-pill bg-ink-100 px-2 py-0.5 text-xs font-medium text-secondary">аноним на сайте</span>
                                @endif
                            </p>
                            <p class="text-xs break-all text-secondary">{{ $donation->donor_email }}</p>
                            @if ($donation->comment)
                                <p class="mt-1 text-xs text-secondary">«{{ Str::limit($donation->comment, 120) }}»</p>
                            @endif
                        </td>
                        <td class="min-w-48">
                            @if ($donation->campaign)
                                <a href="{{ route('shelter.campaigns.edit', $donation->campaign) }}" class="text-primary hover:underline">{{ $donation->campaign->title }}</a>
                            @else
                                <span class="text-secondary">Общий фонд</span>
                            @endif
                        </td>
                        <td class="text-right font-semibold whitespace-nowrap tabular-nums">{{ Money::format($donation->amount) }}</td>
                        <td><x-badge :status="$donation->status" /></td>
                    </tr>
                @endforeach
            </x-table>

            <x-pagination :paginator="$donations" />
        @endif
    </div>
</x-layouts.cabinet>
