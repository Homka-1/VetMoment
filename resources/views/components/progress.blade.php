@props([
    'raised',
    'goal',
    'compact' => false,
])
@php
    // Прогресс сбора: заливка бирочного цвета с тёмной засечкой; при достижении цели — зелёная полоса.
    // Суммы — в тиынах.
    $percent = $goal > 0 ? (int) min(100, floor($raised * 100 / $goal)) : 0;
    $done = $goal > 0 && $raised >= $goal;
@endphp
<div {{ $attributes->class('grid gap-2') }}>
    @unless ($compact)
        <div class="flex items-baseline justify-between gap-3 text-sm font-semibold">
            <span>{{ \App\Support\Money::format($raised) }} из {{ \App\Support\Money::format($goal) }}</span>
            @if ($done)
                <span class="text-success-fg">Цель достигнута</span>
            @else
                <span class="tabular-nums">{{ $percent }}%</span>
            @endif
        </div>
    @endunless
    <div class="relative h-2 overflow-hidden rounded-pill bg-ink-100" role="progressbar" aria-valuemin="0" aria-valuemax="100"
         aria-valuenow="{{ $percent }}" aria-label="Собрано {{ $percent }}% от цели">
        <div @class(['vm-progress-fill h-full rounded-pill', 'bg-success-solid' => $done, 'bg-tag-500' => ! $done])
             style="width: {{ $percent }}%">
            @if (! $done && $percent > 0)
                <span class="absolute top-0 h-full w-0.5 bg-ink-900" style="left: calc({{ $percent }}% - 2px)"></span>
            @endif
        </div>
    </div>
</div>
