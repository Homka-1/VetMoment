@use('App\Support\Money')
@props([
    'items' => [],
    'total' => null,
    'totalLabel' => 'Итого',
])
{{-- Смета или расходы: строки «статья — сумма» (App\Support\Estimate::parse), разделители subtle. --}}
<dl {{ $attributes->class('divide-y divide-subtle') }}>
    @foreach ($items as $item)
        <div class="flex items-baseline justify-between gap-4 py-2.5">
            <dt class="text-secondary">{{ $item['label'] }}</dt>
            <dd class="shrink-0 font-semibold tabular-nums">{{ Money::format($item['amount']) }}</dd>
        </div>
    @endforeach
    @if ($total !== null)
        <div class="flex items-baseline justify-between gap-4 py-2.5">
            <dt class="font-semibold">{{ $totalLabel }}</dt>
            <dd class="shrink-0 font-semibold tabular-nums">{{ Money::format($total) }}</dd>
        </div>
    @endif
</dl>
