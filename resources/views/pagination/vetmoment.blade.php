{{-- Пагинация (UI-кит, артборд 05): квадраты 44 px, текущая — тёмная. Подключена по умолчанию в AppServiceProvider. --}}
@if ($paginator->hasPages())
    @php
        $box = 'grid h-control min-w-control place-items-center rounded-sm border-control px-2 text-sm font-semibold tabular-nums transition-colors duration-120';
    @endphp
    <nav aria-label="Страницы" class="flex min-w-0 justify-center">
        <ul class="flex flex-wrap items-center gap-2">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="{{ $box }} border-ink-200 text-ink-400" aria-disabled="true" aria-label="Предыдущая страница">
                        <x-icon name="chevron-left" />
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $box }} border-ink-900 hover:bg-ink-50" aria-label="Предыдущая страница">
                        <x-icon name="chevron-left" />
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="grid h-control w-6 place-items-center text-muted" aria-hidden="true">…</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="{{ $box }} border-ink-900 bg-ink-900 text-white" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $box }} border-ink-300 hover:border-ink-900" aria-label="Страница {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $box }} border-ink-900 hover:bg-ink-50" aria-label="Следующая страница">
                        <x-icon name="chevron-right" />
                    </a>
                @else
                    <span class="{{ $box }} border-ink-200 text-ink-400" aria-disabled="true" aria-label="Следующая страница">
                        <x-icon name="chevron-right" />
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
