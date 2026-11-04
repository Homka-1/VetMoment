@props([
    'pet',
    'href' => null,
    'image' => null,
    'favorited' => false,
    'favoriteUrl' => null,
    'compact' => false,
    'headingLevel' => 3,
])
@php
    // Карточка питомца (UI-кит, артборд 04): фото со статусом и «в избранное», кличка, возраст и пол,
    // до трёх черт характера, приют и готовность к выгулу. Вся карточка — одна ссылка (растянутая).
    $image ??= $pet->relationLoaded('cover') && $pet->cover ? $pet->cover->url() : null;
    $traits = array_slice(array_values($pet->traitLabels()), 0, $compact ? 2 : 3);
    $shelter = $pet->relationLoaded('shelter') ? $pet->shelter : null;
    $href ??= \Illuminate\Support\Facades\Route::has('pets.show') ? route('pets.show', $pet) : '#';
    $photoTones = ['bg-sky-100', 'bg-tag-100', 'bg-meadow-100', 'bg-ink-100'];
    $photoTone = $photoTones[crc32((string) $pet->name) % 4];
@endphp
<article {{ $attributes->class('group relative flex flex-col rounded-lg border border-subtle bg-raised p-2 transition-[box-shadow,border-color] duration-200 hover:border-ink-300 hover:shadow-md focus-within:border-ink-300') }}>
    <div @class(['relative overflow-hidden rounded-md', $photoTone, 'aspect-[4/3.4]'])>
        @if ($image)
            <img src="{{ $image }}" alt="{{ $pet->name }}, {{ mb_strtolower($pet->species->label()) }}" loading="lazy" decoding="async"
                 class="size-full object-cover">
        @else
            <img src="{{ asset('images/illustrations/'.($pet->species === \App\Enums\Species::Cat ? 'cat-smoke' : 'dog-caramel').'.svg') }}"
                 alt="" class="absolute inset-x-0 bottom-0 mx-auto h-[86%] w-auto">
        @endif
        <x-badge :status="$pet->status" class="absolute top-3 left-3 border-0 bg-white! shadow-sm" />
        @if ($favoriteUrl)
            <form method="POST" action="{{ $favoriteUrl }}" class="absolute top-2.5 right-2.5 z-10">
                @csrf
                <button type="submit" aria-pressed="{{ $favorited ? 'true' : 'false' }}"
                        aria-label="{{ $favorited ? 'Убрать '.$pet->name.' из избранного' : 'Добавить '.$pet->name.' в избранное' }}"
                        class="grid size-10 place-items-center rounded-pill bg-white text-ink-900 shadow-sm transition-colors hover:bg-ink-50">
                    <x-icon :name="$favorited ? 'heart-filled' : 'heart'" @class(['text-danger-solid' => $favorited]) />
                </button>
            </form>
        @endif
    </div>

    <div @class(['flex flex-1 flex-col px-3 pt-4', $compact ? 'pb-3' : 'pb-4'])>
        <h{{ $headingLevel }} class="font-display text-lg leading-snug font-semibold tracking-display">
            <a href="{{ $href }}" class="rounded-xs after:absolute after:inset-0 after:rounded-lg focus-visible:outline-none focus-visible:after:outline-3 focus-visible:after:outline-offset-2 focus-visible:after:outline-focus">{{ $pet->name }}</a>
        </h{{ $headingLevel }}>
        <p class="mt-1 text-sm text-secondary">{{ $pet->summary() }}</p>

        @if ($traits)
            <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="Характер">
                @foreach ($traits as $trait)
                    <li class="rounded-pill bg-ink-100 px-2.5 py-1 text-xs font-medium text-secondary">{{ mb_strtolower($trait) }}</li>
                @endforeach
            </ul>
        @endif

        @unless ($compact)
            <div class="min-h-4 grow"></div>
            <div class="grid gap-1.5 border-t border-subtle pt-3 text-sm">
                @if ($shelter)
                    <p class="flex items-center gap-2 text-secondary">
                        <x-icon name="home" size="sm" />{{ $shelter->name }}, {{ $shelter->city }}
                    </p>
                @endif
                @if ($pet->isWalkable())
                    <p class="flex items-center gap-2 font-semibold text-meadow-700">
                        <x-icon name="leash" size="sm" />Можно погулять
                    </p>
                @endif
            </div>
        @endunless
    </div>
</article>
