@props([
    'images' => [],
    'alt' => '',
])
{{-- Галерея фото питомца: большое фото + миниатюры. $images — список URL.
     Скрипт (resources/js/ui/gallery.js) меняет большое фото; без JS миниатюры — ссылки на файлы. --}}
@if ($images)
    <div {{ $attributes->class('grid gap-3') }} data-gallery>
        <div class="aspect-square overflow-hidden rounded-lg bg-ink-100 md:aspect-[4/3]">
            <img src="{{ $images[0] }}" alt="{{ $alt }}" class="size-full object-cover" data-gallery-main>
        </div>
        @if (count($images) > 1)
            <ul class="flex gap-2 overflow-x-auto pb-1" aria-label="Все фото">
                @foreach ($images as $i => $src)
                    <li class="shrink-0">
                        <a href="{{ $src }}" data-gallery-thumb="{{ $src }}" @if ($i === 0) aria-current="true" @endif
                           class="block size-16 overflow-hidden rounded-sm border-2 border-transparent aria-[current=true]:border-ink-900 md:size-20">
                            <img src="{{ $src }}" alt="Фото {{ $i + 1 }} из {{ count($images) }}" loading="lazy" class="size-full object-cover">
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
