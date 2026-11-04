@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'submit',
    'icon' => null,
    'iconRight' => null,
    'iconOnly' => false,
    'label' => null,
    'loading' => false,
    'block' => false,
    'disabled' => false,
])
@php
    // UI-кит, артборд 02: 5 видов × размеры sm 36 / md 44 / lg 52, радиус sm (10px).
    // Жёлтая donate — только для действий с деньгами.
    $variants = [
        'primary' => 'bg-ink-900 text-white hover:bg-ink-800 active:bg-ink-950',
        'donate' => 'bg-tag-500 text-ink-900 hover:bg-tag-600 active:bg-tag-press',
        'secondary' => 'border-control border-ink-900 bg-white text-ink-900 hover:bg-ink-50 active:bg-ink-100',
        'ghost' => 'bg-transparent text-ink-900 hover:bg-ink-100 active:bg-ink-200',
        'danger' => 'bg-danger-solid text-white hover:bg-danger-hover active:bg-danger-press',
    ];
    $disabledClasses = $variant === 'ghost'
        ? 'disabled:bg-transparent disabled:text-ink-400 aria-disabled:text-ink-400'
        : ($variant === 'secondary'
            ? 'disabled:border-ink-300 disabled:bg-white disabled:text-ink-400 aria-disabled:border-ink-300 aria-disabled:text-ink-400'
            : 'disabled:bg-ink-200 disabled:text-ink-400 aria-disabled:bg-ink-200 aria-disabled:text-ink-400');
    $sizes = $iconOnly
        ? ['sm' => 'size-control-sm', 'md' => 'size-control', 'lg' => 'size-control-lg']
        : ['sm' => 'h-control-sm px-3 text-sm gap-1.5', 'md' => 'h-control px-5 text-base gap-2', 'lg' => 'h-control-lg px-6 text-md gap-2'];
    $iconSize = $size === 'sm' ? 'sm' : 'md';

    $classes = implode(' ', [
        'relative inline-flex items-center justify-center rounded-sm font-semibold leading-tight whitespace-nowrap select-none',
        'transition-colors duration-120 disabled:cursor-not-allowed aria-disabled:pointer-events-none',
        $variants[$variant] ?? $variants['primary'],
        $disabledClasses,
        $sizes[$size] ?? $sizes['md'],
        $block ? 'w-full' : '',
    ]);
    $ariaLabel = $iconOnly ? ($label ?? trim(strip_tags($slot))) : null;
@endphp
@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->class($classes) }} @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif>
@elseif ($href)
    <a {{ $attributes->class($classes) }} role="link" aria-disabled="true" @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }} @disabled($disabled)
            @if ($loading) aria-busy="true" @endif @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif>
@endif
    @if ($loading)
        <span class="absolute inset-0 grid place-items-center" aria-hidden="true">
            <span class="size-4 animate-spin rounded-pill border-2 border-current border-r-transparent"></span>
        </span>
        <span class="sr-only">Загрузка</span>
    @endif
    <span @class(['inline-flex items-center justify-center gap-[inherit]', 'invisible' => $loading])>
        @if ($icon)
            <x-icon :name="$icon" :size="$iconSize" />
        @endif
        @unless ($iconOnly)
            {{ $slot }}
        @endunless
        @if ($iconRight)
            <x-icon :name="$iconRight" :size="$iconSize" />
        @endif
    </span>
@if ($href)
    </a>
@else
    </button>
@endif
