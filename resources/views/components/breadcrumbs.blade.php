@props(['items' => []])
{{-- Хлебные крошки: $items = ['Главная' => url, 'Питомцы' => url, 'Бублик' => null]; последний — текущая страница. --}}
<nav aria-label="Хлебные крошки" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
        @foreach ($items as $label => $url)
            <li class="flex items-center gap-2">
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="text-secondary underline underline-offset-3 hover:text-primary">{{ $label }}</a>
                    <x-icon name="chevron-right" size="sm" class="text-ink-400" />
                @else
                    <span class="font-semibold text-primary" aria-current="page">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
