@props([
    'padding' => 'md',
    'interactive' => false,
    'tone' => 'raised',
    'as' => 'div',
])
@php
    // Карточка: без тени, граница border.subtle, радиус lg. Тень md — только при наведении на интерактивную.
    $paddings = ['none' => '', 'sm' => 'p-4', 'md' => 'p-5 md:p-6', 'lg' => 'p-6 md:p-8'];
    $tones = ['raised' => 'bg-raised', 'canvas' => 'bg-canvas', 'adopt' => 'bg-path-adopt border-transparent', 'donate' => 'bg-path-donate border-transparent', 'walk' => 'bg-path-walk border-transparent'];
@endphp
<{{ $as }} {{ $attributes->class([
    'rounded-lg border border-subtle',
    $tones[$tone] ?? $tones['raised'],
    $paddings[$padding] ?? $paddings['md'],
    'relative transition-[box-shadow,border-color] duration-200 hover:border-ink-300 hover:shadow-md focus-within:border-ink-300' => $interactive,
]) }}>
    {{ $slot }}
</{{ $as }}>
