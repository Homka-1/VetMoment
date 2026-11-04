@props([
    'images' => [],
    'alt' => '',
])
@php
    // Галерея фото питомца: большое фото + миниатюры + счётчик «1 / 6».
    // $images — список URL или массивов ['src' => …, 'thumb' => …, 'alt' => …].
    // Скрипт (resources/js/ui/gallery.js) меняет большое фото; без JS миниатюры — ссылки на файлы.
    $items = array_map(fn ($image) => is_array($image) ? $image + ['thumb' => $image['src'], 'alt' => $alt] : ['src' => $image, 'thumb' => $image, 'alt' => $alt], array_values($images));
    $total = count($items);
@endphp
@if ($items)
    <div {{ $attributes->class('grid gap-3') }} data-gallery>
        <div class="relative aspect-square overflow-hidden rounded-lg bg-ink-100 md:aspect-[4/3]">
            <img src="{{ $items[0]['src'] }}" alt="{{ $items[0]['alt'] }}" class="size-full object-cover" data-gallery-main>
            @if ($total > 1)
                <p class="absolute right-4 bottom-4 inline-flex h-8 items-center gap-1.5 rounded-pill bg-white px-3 text-sm font-semibold shadow-sm tabular-nums">
                    <x-icon name="camera" size="sm" />
                    <span><span class="sr-only">Фото </span><span data-gallery-index>1</span> / {{ $total }}</span>
                </p>
            @endif
        </div>
        @if ($total > 1)
            <ul class="grid grid-cols-[repeat(auto-fill,minmax(64px,1fr))] gap-2 sm:grid-cols-4 md:gap-3" aria-label="Все фото">
                @foreach ($items as $i => $item)
                    <li>
                        <a href="{{ $item['src'] }}" data-gallery-thumb="{{ $item['src'] }}" data-gallery-alt="{{ $item['alt'] }}" data-gallery-position="{{ $i + 1 }}"
                           @if ($i === 0) aria-current="true" @endif
                           class="block aspect-[4/3] overflow-hidden rounded-md border-2 border-transparent transition-colors hover:border-ink-300 aria-[current=true]:border-ink-900">
                            <img src="{{ $item['thumb'] }}" alt="Фото {{ $i + 1 }} из {{ $total }}" loading="lazy" decoding="async" class="size-full object-cover">
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
