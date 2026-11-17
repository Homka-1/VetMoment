@use('App\Support\Seo')
@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'noindex' => false,
    'canonical' => null,
])
{{-- Общий <head> всех layout-ов. Шрифты — локальные (resources/fonts), без внешних CDN.
     SEO: description, canonical, Open Graph и карточка для мессенджеров/соцсетей (App\Support\Seo).
     noindex — для кабинетов, форм и служебных страниц; у таких страниц нет canonical. --}}
@php
    $fullTitle = $title ? $title.' — VetMoment' : 'VetMoment — помощь бездомным животным и приютам';
    $description ??= Seo::DEFAULT_DESCRIPTION;
    $canonical ??= Seo::canonical();
    $image = Seo::absolute($image ?? Seo::DEFAULT_IMAGE);
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
@if ($noindex)
    <meta name="robots" content="noindex, nofollow">
@else
    <link rel="canonical" href="{{ $canonical }}">
@endif
<meta property="og:site_name" content="VetMoment">
<meta property="og:locale" content="ru_RU">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title ?? $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
@if ($image === Seo::absolute(Seo::DEFAULT_IMAGE))
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
@endif
<meta property="og:image:alt" content="{{ $title ?? 'VetMoment' }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#1D2B4F">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ asset('favicon-32.png') }}" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
{{ $slot }}
