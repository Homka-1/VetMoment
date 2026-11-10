@props([
    'pet' => null,
    'size' => 'md',
])
@php
    // Квадратная миниатюра питомца: превью обложки или иллюстрация на тоне по кличке (как в карточке).
    // Без питомца — «любая собака» (иллюстрация). Для фото нужна загруженная связь cover.
    $image = $pet && $pet->relationLoaded('cover') && $pet->cover ? $pet->cover->thumbUrl() : null;
    $tones = ['bg-sky-100', 'bg-tag-100', 'bg-meadow-100', 'bg-ink-100'];
    $tone = $pet ? $tones[crc32((string) $pet->name) % 4] : 'bg-meadow-100';
    $sizes = ['sm' => 'size-10 rounded-sm', 'md' => 'size-16 rounded-md', 'lg' => 'size-20 rounded-md md:size-24'];
    $art = $pet?->species === \App\Enums\Species::Cat ? 'cat-smoke' : 'dog-caramel';
@endphp
<span {{ $attributes->class(['relative block shrink-0 overflow-hidden', $sizes[$size] ?? $sizes['md'], $tone]) }}>
    @if ($image)
        <img src="{{ $image }}" alt="" loading="lazy" class="size-full object-cover">
    @else
        <img src="{{ asset('images/illustrations/'.$art.'.svg') }}" alt="" class="absolute inset-x-0 bottom-0 mx-auto h-[88%] w-auto">
    @endif
</span>
