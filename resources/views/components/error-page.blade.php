@props([
    'code',
    'title',
    'illustration' => 'dog-graphite',
    'guest' => false,
])
{{-- Страница ошибки (макет 12-states): портрет в круге и бирка с кодом — мотив айдентики, ниже заголовок,
     что случилось и что делать, и два действия. guest — без сессии и БД (для 500/503). --}}
<x-layouts.app :title="$title" noindex :guest="$guest">
    <section class="page-container flex flex-col items-center py-14 text-center lg:py-20" aria-labelledby="error-title">
        <div class="relative">
            <span class="grid size-48 place-items-end overflow-hidden rounded-pill bg-sky-100 sm:size-60">
                <img src="{{ asset('images/illustrations/'.$illustration.'.svg') }}" alt="" class="mx-auto h-[92%] w-auto" width="200" height="200">
            </span>
            <span class="vm-tag-swing absolute -right-6 -bottom-3 grid size-22 place-items-center rounded-pill bg-tag-500 sm:-right-14 sm:-bottom-4 sm:size-28" aria-hidden="true">
                <span class="absolute top-2 left-1/2 size-2.5 -translate-x-1/2 rounded-pill border-2 border-ink-900 bg-white"></span>
                <span class="mt-2 -rotate-6 font-display text-xl font-bold text-ink-900 sm:text-[28px]">{{ $code }}</span>
            </span>
        </div>
        <h1 id="error-title" class="type-h1 mt-10 max-w-2xl">{{ $title }}</h1>
        <p class="mt-4 max-w-lg text-secondary">{{ $slot }}</p>
        @isset($actions)
            <div class="mt-8 flex flex-wrap justify-center gap-3">{{ $actions }}</div>
        @endisset
        <p class="sr-only">Код ошибки {{ $code }}.</p>
    </section>
</x-layouts.app>
