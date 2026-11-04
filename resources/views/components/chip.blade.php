@props([
    'active' => false,
    'href' => null,
    'count' => null,
    'name' => null,
    'value' => null,
])
@php
    // Чип фильтра каталога. Три режима: ссылка (href), чекбокс в форме фильтров (name), кнопка.
    $base = 'relative inline-flex h-control-sm items-center gap-1.5 rounded-pill border-control px-3.5 text-sm font-medium whitespace-nowrap transition-colors duration-120 cursor-pointer';
    $off = 'border-ink-300 bg-white text-primary hover:border-ink-900';
    $on = 'border-ink-900 bg-ink-900 text-white';
@endphp
@if ($name)
    <label {{ $attributes->class([$base, $off, 'has-checked:border-ink-900 has-checked:bg-ink-900 has-checked:text-white has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus']) }}>
        <input type="checkbox" name="{{ $name }}" value="{{ $value }}" class="peer sr-only" @checked($active)>
        <x-icon name="check" size="sm" class="hidden peer-checked:inline-block" />
        <span>{{ $slot }}</span>
        @if (! is_null($count))
            <span class="opacity-70 tabular-nums">{{ $count }}</span>
        @endif
    </label>
@else
    @php $tag = $href ? 'a' : 'button'; @endphp
    <{{ $tag }} @if ($href) href="{{ $href }}" @else type="button" @endif
        @if ($active) aria-current="true" @endif
        {{ $attributes->class([$base, $active ? $on : $off]) }}>
        @if ($active)
            <x-icon name="check" size="sm" />
        @endif
        <span>{{ $slot }}</span>
        @if (! is_null($count))
            <span class="opacity-70 tabular-nums">{{ $count }}</span>
        @endif
    </{{ $tag }}>
@endif
