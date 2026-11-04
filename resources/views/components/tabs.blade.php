@props([
    'items' => [],
    'label' => 'Разделы',
])
{{-- Вкладки-ссылки (UI-кит, артборд 05): активная подчёркнута ink-900.
     $items = [['label' => 'Предстоящие', 'href' => url, 'active' => true, 'count' => 3], ...] --}}
<nav aria-label="{{ $label }}" {{ $attributes->class('border-b border-subtle') }}>
    <ul class="-mb-px flex gap-6 overflow-x-auto">
        @foreach ($items as $item)
            <li class="shrink-0">
                <a href="{{ $item['href'] }}" @if ($item['active'] ?? false) aria-current="page" @endif
                   @class([
                       'inline-flex h-control items-center gap-2 border-b-2 text-sm font-semibold transition-colors duration-120',
                       'border-ink-900 text-primary' => $item['active'] ?? false,
                       'border-transparent text-muted hover:border-ink-300 hover:text-primary' => ! ($item['active'] ?? false),
                   ])>
                    {{ $item['label'] }}
                    @isset($item['count'])
                        <span class="rounded-pill bg-ink-100 px-2 py-0.5 text-xs tabular-nums text-secondary">{{ $item['count'] }}</span>
                    @endisset
                </a>
            </li>
        @endforeach
    </ul>
</nav>
