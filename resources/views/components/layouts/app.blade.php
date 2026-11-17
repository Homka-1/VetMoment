@props([
    'title' => null,
    'description' => null,
    'canvas' => false,
    'image' => null,
    'type' => 'website',
    'noindex' => false,
    'guest' => false,
])
{{-- Основной layout сайта: шапка, контент, подвал, мобильное меню и тосты.
     Использование: <x-layouts.app title="Каталог"> … </x-layouts.app>; слот head — доп. теги в <head>;
     image/type — Open Graph, noindex — закрыть страницу от поисковиков (кабинеты, формы);
     guest — без сессии и БД в шапке (страницы 500/503). --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <x-site.head :title="$title" :description="$description" :image="$image" :type="$type" :noindex="$noindex">
        {{ $head ?? '' }}
    </x-site.head>
</head>
<body @class(['flex min-h-dvh flex-col', 'bg-canvas' => $canvas, 'bg-page' => ! $canvas])>
    <x-site.icon-sprite />
    <a href="#content" class="sr-only z-50 rounded-sm bg-ink-900 px-4 py-3 font-semibold text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Перейти к содержанию</a>

    <x-site.header :guest="$guest" />

    <main id="content" tabindex="-1" {{ $attributes->class('flex-1 focus:outline-none') }}>
        {{ $slot }}
    </main>

    <x-site.footer :guest="$guest" />
    <x-site.mobile-menu :guest="$guest" />
    @unless ($guest)
        <x-flash />
    @endunless
</body>
</html>
