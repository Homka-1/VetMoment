@props([
    'tone' => 'info',
    'title' => null,
    'icon' => null,
])
@php
    // Баннер внутри страницы: сводка ошибок формы, «приют на модерации», пояснения.
    $tones = [
        'info' => ['box' => 'bg-info-bg border-info-border', 'fg' => 'text-info-fg', 'icon' => 'info'],
        'success' => ['box' => 'bg-success-bg border-success-border', 'fg' => 'text-success-fg', 'icon' => 'check-circle'],
        'warning' => ['box' => 'bg-warning-bg border-warning-border', 'fg' => 'text-warning-fg', 'icon' => 'clock'],
        'danger' => ['box' => 'bg-danger-bg border-danger-border', 'fg' => 'text-danger-fg', 'icon' => 'alert-circle'],
    ];
    $t = $tones[$tone] ?? $tones['info'];
@endphp
<div {{ $attributes->class(['flex items-start gap-3 rounded-md border p-4', $t['box']]) }}
     role="{{ $tone === 'danger' ? 'alert' : 'status' }}">
    <x-icon :name="$icon ?? $t['icon']" @class(['mt-0.5', $t['fg']]) />
    <div class="min-w-0 flex-1 text-sm text-primary">
        @if ($title)
            <p @class(['font-semibold', $t['fg']])>{{ $title }}</p>
        @endif
        @if ($slot->isNotEmpty())
            <div @class(['grid gap-1', 'mt-1' => $title])>{{ $slot }}</div>
        @endif
    </div>
    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</div>
