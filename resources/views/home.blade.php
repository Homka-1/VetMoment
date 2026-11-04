<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — помощь бездомным животным</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen grid place-items-center p-6 font-sans">
    {{-- Техническая заглушка этапа 4: проверка, что приложение и сборка ассетов работают.
         Дизайн, layout и компоненты — этап 7. --}}
    <main class="max-w-xl text-center">
        <h1 class="text-3xl font-bold">{{ config('app.name') }}</h1>
        <p class="mt-4">Сервис помощи бездомным животным и приютам: анкеты питомцев, сбор пожертвований, запись на выгул.</p>
        <p class="mt-2 text-sm">Сайт в разработке.</p>
    </main>
</body>
</html>
