@props([
    'title' => null,
    'description' => 'VetMoment — анкеты питомцев из приютов Казахстана, сборы на лечение и корм, запись на выгул собак.',
])
{{-- Общий <head> всех layout-ов. Шрифты — локальные (resources/fonts), без внешних CDN. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $title ? $title.' — VetMoment' : 'VetMoment — помощь бездомным животным и приютам' }}</title>
<meta name="description" content="{{ $description }}">
<meta name="theme-color" content="#1D2B4F">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ asset('favicon-32.png') }}" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
{{ $slot }}
