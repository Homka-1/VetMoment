@use('App\Enums\CampaignStatus')
@use('App\Support\Money')
@use('App\Support\Plural')
@props([
    'campaign',
    'href' => null,
    'donateHref' => null,
    'image' => null,
    'headingLevel' => 3,
])
@php
    // Карточка сбора (UI-кит, артборд 04): собранная сумма — главное число; срок и число помогших рядом.
    $shelter = $campaign->relationLoaded('shelter') ? $campaign->shelter : null;
    $href ??= \Illuminate\Support\Facades\Route::has('campaigns.show') ? route('campaigns.show', $campaign) : '#';
    $donateHref ??= $href;
    $done = $campaign->status === CampaignStatus::Completed || ($campaign->goal_amount > 0 && $campaign->raised_amount >= $campaign->goal_amount);
    $active = $campaign->status === CampaignStatus::Active && ! $done;
    $daysLeft = $campaign->ends_at && $campaign->ends_at->isFuture() ? (int) ceil(now()->diffInDays($campaign->ends_at)) : null;
    $percent = $campaign->progressPercent();
@endphp
<article {{ $attributes->class('flex flex-col rounded-lg border border-subtle bg-raised p-2') }}>
    <div @class(['relative h-40 overflow-hidden rounded-md md:h-44', $done ? 'bg-sky-100' : 'bg-tag-100'])>
        @if ($image)
            <img src="{{ $image }}" alt="" loading="lazy" decoding="async" class="size-full object-cover">
        @else
            <img src="{{ asset('images/illustrations/dog-caramel.svg') }}" alt="" class="absolute inset-x-0 bottom-0 mx-auto h-[90%] w-auto">
        @endif
        <x-badge :status="$done ? CampaignStatus::Completed : $campaign->status" @class(['absolute top-3 left-3', 'border-0 bg-white! shadow-sm' => $done]) />
    </div>

    <div class="flex flex-1 flex-col px-3 pt-4 pb-3">
        <h{{ $headingLevel }} class="font-display text-lg leading-snug font-semibold tracking-display">
            <a href="{{ $href }}" class="hover:underline hover:decoration-tag-600 hover:decoration-2 hover:underline-offset-4">{{ $campaign->title }}</a>
        </h{{ $headingLevel }}>
        @if ($shelter)
            <p class="mt-1 text-sm text-secondary">{{ $shelter->name }}, {{ $shelter->city }}</p>
        @endif

        <div class="min-h-4 grow"></div>

        <p class="flex flex-wrap items-baseline gap-x-2">
            <span class="type-number">{{ Money::format($campaign->raised_amount) }}</span>
            <span class="text-sm text-secondary">из {{ Money::format($campaign->goal_amount) }}</span>
            <span class="ml-auto text-sm font-semibold tabular-nums">{{ $percent }}%</span>
        </p>
        <x-progress :raised="$campaign->raised_amount" :goal="$campaign->goal_amount" compact class="mt-3" />

        <div class="mt-3 flex items-center justify-between gap-3 text-sm text-secondary">
            <span class="flex items-center gap-1.5"><x-icon name="users" size="sm" />{{ Plural::format((int) $campaign->donations_count, ['человек', 'человека', 'человек']) }}</span>
            @if ($done)
                <span class="flex items-center gap-1.5 font-semibold text-success-fg"><x-icon name="check-circle" size="sm" />Собрано</span>
            @elseif ($daysLeft !== null)
                <span class="flex items-center gap-1.5"><x-icon name="clock" size="sm" />Ещё {{ Plural::format($daysLeft, ['день', 'дня', 'дней']) }}</span>
            @endif
        </div>

        <div class="mt-4">
            @if ($active)
                <x-button variant="donate" :href="$donateHref" icon="heart" block>Помочь сбору</x-button>
            @else
                <x-button variant="secondary" :href="$href" block>Смотреть отчёт</x-button>
            @endif
        </div>
    </div>
</article>
