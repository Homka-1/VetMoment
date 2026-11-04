@props([
    'title' => null,
    'description' => null,
    'canvas' => false,
])
{{-- Основной layout сайта: шапка, контент, подвал, мобильное меню и тосты.
     Использование: <x-layouts.app title="Каталог"> … </x-layouts.app>; слот head — доп. теги в <head>. --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <x-site.head :title="$title" :description="$description ?? 'VetMoment — анкеты питомцев из приютов Казахстана, сборы на лечение и корм, запись на выгул собак.'">
        {{ $head ?? '' }}
    </x-site.head>
</head>
<body @class(['flex min-h-dvh flex-col', 'bg-canvas' => $canvas, 'bg-page' => ! $canvas])>
    <x-site.icon-sprite />
    <a href="#content" class="sr-only z-50 rounded-sm bg-ink-900 px-4 py-3 font-semibold text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Перейти к содержанию</a>

    <x-site.header />

    <main id="content" tabindex="-1" {{ $attributes->class('flex-1 focus:outline-none') }}>
        {{ $slot }}
    </main>

    <x-site.footer />
    <x-site.mobile-menu />
    <x-flash />
</body>
</html>
