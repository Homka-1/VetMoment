@props([
    'variant' => 'light',
    'href' => null,
])
@php
    $src = asset($variant === 'inverse' ? 'images/brand/logo-inverse.svg' : 'images/brand/logo.svg');
    $href ??= route('home');
@endphp
<a href="{{ $href }}" {{ $attributes->class('inline-flex shrink-0 rounded-sm') }}>
    {{-- Логотип в кривых (design/brand), высота 32 / 36 px --}}
    <img src="{{ $src }}" alt="VetMoment — на главную" width="184" height="32" class="h-8 w-auto lg:h-9">
</a>
