@props([
    'name',
    'size' => 'md',
    'label' => null,
])
@php
    // Иконки из спрайта design/icons/icons.svg (встроен в layout: resources/svg/icons.svg).
    // Размеры: sm 16 · md 20 · lg 24 (tokens.json → size.icon).
    $px = match ($size) {
        'sm' => 16,
        'md' => 20,
        'lg' => 24,
        default => (int) $size,
    };
@endphp
<svg {{ $attributes->class('shrink-0') }} width="{{ $px }}" height="{{ $px }}"
     @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif>
    <use href="#i-{{ $name }}"></use>
</svg>
