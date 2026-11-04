@props([
    'path' => 'adopt',
    'title',
    'href',
    'action',
    'headingLevel' => 3,
])
@php
    // Три пути помощи (UI-кит, артборд 04): небо — забрать, бирка — пожертвовать, луг — погулять.
    $paths = [
        'adopt' => ['bg' => 'bg-path-adopt', 'icon' => 'home', 'fg' => 'text-sky-700'],
        'donate' => ['bg' => 'bg-path-donate', 'icon' => 'tenge', 'fg' => 'text-tag-700'],
        'walk' => ['bg' => 'bg-path-walk', 'icon' => 'leash', 'fg' => 'text-meadow-700'],
    ];
    $p = $paths[$path] ?? $paths['adopt'];
@endphp
<article {{ $attributes->class(['relative flex flex-col rounded-lg p-6 md:p-8', $p['bg']]) }}>
    <span @class(['grid size-14 place-items-center rounded-pill bg-white', $p['fg']])>
        <x-icon :name="$p['icon']" size="lg" />
    </span>
    <h{{ $headingLevel }} class="type-h3 mt-6">{{ $title }}</h{{ $headingLevel }}>
    <p class="mt-3 text-secondary">{{ $slot }}</p>
    <div class="min-h-6 grow"></div>
    <a href="{{ $href }}" class="link inline-flex items-center gap-1 self-start after:absolute after:inset-0 after:rounded-lg focus-visible:outline-none focus-visible:after:outline-3 focus-visible:after:outline-offset-2 focus-visible:after:outline-focus">
        {{ $action }}<x-icon name="chevron-right" size="sm" />
    </a>
</article>
