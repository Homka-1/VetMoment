@props([
    'title',
    'illustration' => 'dog-caramel',
    'tone' => 'meadow',
    'headingLevel' => 2,
])
{{-- Пустое состояние: подсказывает следующий шаг, а не грустит (UI-кит, артборд 05).
     Слот — пояснение, слот action — кнопка. --}}
<div {{ $attributes->class('flex flex-col items-center rounded-lg bg-canvas px-6 py-12 text-center md:py-16') }}>
    <span @class([
        'relative grid size-32 place-items-end overflow-hidden rounded-pill md:size-36',
        'bg-meadow-100' => $tone === 'meadow',
        'bg-sky-100' => $tone === 'sky',
        'bg-tag-100' => $tone === 'tag',
    ])>
        <img src="{{ asset('images/illustrations/'.$illustration.'.svg') }}" alt="" class="mx-auto h-[92%] w-auto">
    </span>
    <h{{ $headingLevel }} class="type-h4 mt-6 font-semibold">{{ $title }}</h{{ $headingLevel }}>
    @if ($slot->isNotEmpty())
        <p class="mt-2 max-w-prose text-secondary">{{ $slot }}</p>
    @endif
    @isset($action)
        <div class="mt-6 flex flex-wrap justify-center gap-3">{{ $action }}</div>
    @endisset
</div>
