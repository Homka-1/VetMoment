<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Технический layout этапа 6: формы работают, но без дизайна.
     Шапка, подвал, UI-кит и вёрстка по макетам — этап 7 (этот файл будет заменён). --}}
<body class="min-h-screen p-6 font-sans">
    <main class="mx-auto max-w-xl">
        <p class="mb-6"><a href="{{ route('home') }}" class="underline">{{ config('app.name') }}</a></p>

        <h1 class="mb-4 text-2xl font-bold">@yield('title')</h1>

        @if (session('status'))
            <p role="status" class="mb-4 rounded border p-3">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-4 rounded border p-3">
                <p class="font-bold">Проверьте форму:</p>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
