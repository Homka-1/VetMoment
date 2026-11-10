@use('App\Enums\CampaignStatus')
@use('App\Support\LocalTime')
@use('App\Support\Money')
@use('App\Support\Plural')
@props(['campaign'])
@php
    // Цифры сбора (макет 05, верх правой колонки): собрано крупно, цель, полоса, сколько человек и срок.
    $daysLeft = $campaign->daysLeft();
    $reached = $campaign->isGoalReached();
@endphp
<div {{ $attributes->class('grid gap-4') }}>
    <p class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <span class="font-display text-2xl leading-none md:text-3xl font-semibold tracking-display tabular-nums">{{ Money::format($campaign->raised_amount) }}</span>
        <span class="text-sm text-secondary">из {{ Money::format($campaign->goal_amount) }}</span>
    </p>
    <x-progress :raised="$campaign->raised_amount" :goal="$campaign->goal_amount" compact />
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 text-sm text-secondary">
        <span class="flex items-center gap-1.5"><x-icon name="users" size="sm" />{{ Plural::format((int) $campaign->donations_count, ['человек', 'человека', 'человек']) }}</span>
        @if ($reached)
            <span class="flex items-center gap-1.5 font-semibold text-success-fg"><x-icon name="check-circle" size="sm" />Цель достигнута{{ $campaign->raised_amount > $campaign->goal_amount ? ', '.intdiv($campaign->raised_amount * 100, max(1, $campaign->goal_amount)).'%' : '' }}</span>
        @elseif ($daysLeft !== null)
            <span class="flex items-center gap-1.5"><x-icon name="clock" size="sm" />Ещё {{ Plural::format($daysLeft, ['день', 'дня', 'дней']) }}, до {{ LocalTime::date($campaign->ends_at) }}</span>
        @elseif ($campaign->ends_at)
            <span class="flex items-center gap-1.5"><x-icon name="clock" size="sm" />Закончился {{ LocalTime::date($campaign->ends_at) }}</span>
        @elseif ($campaign->status === CampaignStatus::Active)
            <span class="flex items-center gap-1.5"><x-icon name="clock" size="sm" />До достижения цели</span>
        @endif
    </div>
</div>
