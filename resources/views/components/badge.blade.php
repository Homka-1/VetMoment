@props([
    'status' => null,
    'tone' => null,
    'icon' => null,
    'dashed' => null,
    'solid' => null,
])
@php
    // Бейдж-бирка (UI-кит, артборд 03): слева «пробитое отверстие», статус всегда текстом.
    // Принимает enum статуса (label()/tone()/isDashed()/isSolid()) или tone + слот.
    $toneValue = $tone instanceof \App\Enums\Tone ? $tone->value : ($tone ?? $status?->tone()->value ?? 'neutral');
    $dashed ??= $status && method_exists($status, 'isDashed') && $status->isDashed();
    $solid ??= $status && method_exists($status, 'isSolid') && $status->isSolid();
    $text = $slot->isEmpty() ? $status?->label() : $slot;

    $tones = [
        'neutral' => 'bg-neutral-bg text-neutral-fg border-neutral-border',
        'info' => 'bg-info-bg text-info-fg border-info-border',
        'success' => 'bg-success-bg text-success-fg border-success-border',
        'warning' => 'bg-warning-bg text-warning-fg border-warning-border',
        'danger' => 'bg-danger-bg text-danger-fg border-danger-border',
        'tag' => 'bg-tag-500 text-ink-900 border-tag-500',
        'meadow' => 'bg-meadow-100 text-meadow-700 border-meadow-200',
    ];
    $solidTones = [
        'danger' => 'bg-danger-solid text-white border-danger-solid',
        'success' => 'bg-success-solid text-white border-success-solid',
        'info' => 'bg-info-solid text-white border-info-solid',
    ];
    $classes = $solid ? ($solidTones[$toneValue] ?? $tones[$toneValue]) : ($tones[$toneValue] ?? $tones['neutral']);
@endphp
<span {{ $attributes->class([
    'inline-flex h-7 items-center gap-1.5 rounded-pill border px-2.5 text-xs font-semibold whitespace-nowrap',
    $classes,
    'border-dashed border-ink-400 bg-white' => $dashed,
    'border-transparent' => ! $dashed && ! $solid,
]) }}>
    <span @class(['badge-hole', 'bg-current' => $solid])></span>
    @if ($icon)
        <x-icon :name="$icon" size="sm" class="size-3.5" />
    @endif
    <span>{{ $text }}</span>
</span>
