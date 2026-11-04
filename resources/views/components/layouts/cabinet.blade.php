@use('App\Support\Navigation')
@props([
    'title',
    'heading' => null,
])
{{-- Layout кабинетов (макеты 09–11): боковое меню 256 px слева, контент на фоне canvas.
     На телефоне меню — горизонтальные «таблетки» над контентом. Слот actions — кнопки справа от заголовка. --}}
@php
    $user = auth()->user();
    $items = Navigation::cabinet($user);
@endphp
<x-layouts.app :title="$title" canvas>
    <div class="page-container py-6 lg:grid lg:grid-cols-[var(--spacing-sidebar)_1fr] lg:gap-10 lg:py-10">
        <aside class="lg:sticky lg:top-[calc(var(--spacing-header-lg)+40px)] lg:self-start">
            <div class="hidden items-center gap-3 lg:flex">
                <x-avatar :name="$user->name" size="lg" />
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $user->name }}</p>
                    <p class="text-sm text-muted">{{ $user->role->label() }}</p>
                </div>
            </div>
            <nav aria-label="Разделы кабинета" class="-mx-4 overflow-x-auto px-4 lg:mx-0 lg:mt-6 lg:overflow-visible lg:px-0">
                <ul class="flex gap-2 lg:grid lg:gap-1">
                    @foreach ($items as $item)
                        <li class="shrink-0">
                            <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif
                               @class([
                                   'flex h-control items-center gap-3 rounded-pill border px-4 text-sm font-semibold transition-colors duration-120 lg:rounded-sm lg:border-0',
                                   'border-ink-900 bg-ink-900 text-white lg:bg-white lg:text-primary lg:shadow-sm' => $item['active'],
                                   'border-ink-300 bg-white text-secondary hover:text-primary lg:bg-transparent lg:hover:bg-white' => ! $item['active'],
                               ])>
                                <x-icon :name="$item['icon']" size="sm" @class(['hidden lg:block', 'text-tag-700' => $item['active']]) />{{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="mt-4 hidden border-t border-subtle pt-4 lg:block">
                @csrf
                <button type="submit" class="flex h-control w-full items-center gap-3 rounded-sm px-4 text-sm font-semibold text-secondary hover:bg-white hover:text-primary">
                    <x-icon name="logout" size="sm" />Выйти
                </button>
            </form>
        </aside>

        <div class="mt-6 min-w-0 lg:mt-0">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h1 class="type-h2">{{ $heading ?? $title }}</h1>
                @isset($actions)
                    <div class="flex flex-wrap gap-3">{{ $actions }}</div>
                @endisset
            </div>
            <div class="mt-6 lg:mt-8">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.app>
