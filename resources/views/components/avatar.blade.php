@props([
    'name',
    'src' => null,
    'size' => 'md',
])
@php
    // Аватар: фото или инициалы на одном из трёх фонов путей помощи (стабильно по имени).
    $words = preg_split('/\s+/u', trim($name)) ?: [];
    $initials = mb_strtoupper(mb_substr($words[0] ?? '?', 0, 1).mb_substr($words[1] ?? '', 0, 1));
    $tones = ['bg-sky-200 text-sky-700', 'bg-meadow-200 text-meadow-700', 'bg-tag-200 text-tag-700'];
    $tone = $tones[crc32($name) % 3];
    $sizes = ['sm' => 'size-8 text-xs', 'md' => 'size-10 text-sm', 'lg' => 'size-14 text-md'];
@endphp
<span {{ $attributes->class(['inline-grid shrink-0 place-items-center overflow-hidden rounded-pill font-bold', $sizes[$size] ?? $sizes['md'], $tone => ! $src]) }} aria-hidden="true">
    @if ($src)
        <img src="{{ $src }}" alt="" class="size-full object-cover">
    @else
        {{ $initials }}
    @endif
</span>
