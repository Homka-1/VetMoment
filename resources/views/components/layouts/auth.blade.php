@props([
    'title',
    'heading' => null,
    'illustration' => 'cat-ginger',
    'asideTitle' => 'Рыжик ждёт вас на знакомство',
    'asideText' => 'После входа можно записываться на выгул, следить за заявками и сохранять питомцев в избранное.',
])
{{-- Layout входа и регистрации (макет 08): форма слева, бирочная панель с портретом справа (с lg). --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <x-site.head :title="$title" />
</head>
<body class="min-h-dvh bg-page">
    <x-site.icon-sprite />
    <div class="grid min-h-dvh lg:grid-cols-2">
        <main id="content" class="flex flex-col px-4 py-6 sm:px-10 lg:px-20 lg:py-12 xl:px-30">
            <x-logo />
            <div class="w-full max-w-md py-10 lg:my-auto">
                <h1 class="type-h1">{{ $heading ?? $title }}</h1>
                @isset($subtitle)
                    <p class="mt-3 text-secondary">{{ $subtitle }}</p>
                @endisset
                <div class="mt-8">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <aside class="relative hidden flex-col justify-between overflow-hidden bg-tag-500 p-20 lg:flex" aria-hidden="true">
            <div class="mx-auto mt-12 grid size-80 place-items-end overflow-hidden rounded-pill bg-tag-100 xl:size-88">
                <img src="{{ asset('images/illustrations/'.$illustration.'.svg') }}" alt="" class="mx-auto h-[92%] w-auto">
            </div>
            <div class="max-w-md text-ink-900">
                <p class="type-h3">{{ $asideTitle }}</p>
                <p class="mt-4">{{ $asideText }}</p>
            </div>
        </aside>
    </div>
    <x-flash />
</body>
</html>
