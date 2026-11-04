@use('App\Support\Navigation')
{{-- Шапка (UI-кит, артборд 05): пять разделов, активный подчёркнут бирочной полосой.
     Справа — «Войти» и жёлтая «Помочь»; после входа — уведомления со счётчиком и меню профиля.
     На телефоне (< lg) — логотип, поиск и кнопка меню (x-site.mobile-menu). --}}
@php
    $nav = Navigation::main();
    $user = auth()->user();
    $unread = $user ? $user->unreadNotifications()->count() : 0;
@endphp
<header class="sticky top-0 z-40 border-b border-subtle bg-white/95 backdrop-blur">
    <div class="page-container flex h-header items-center gap-8 lg:h-header-lg">
        <x-logo />

        <nav aria-label="Основные разделы" class="hidden h-full lg:block">
            <ul class="flex h-full items-stretch gap-1">
                @foreach ($nav as $item)
                    <li class="flex">
                        <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif
                           @class([
                               'relative flex items-center px-3 text-sm transition-colors duration-120',
                               'after:absolute after:inset-x-3 after:bottom-0 after:h-[3px] after:rounded-t-xs after:bg-tag-500',
                               'font-semibold text-primary' => $item['active'],
                               'font-medium text-secondary after:opacity-0 hover:text-primary hover:after:bg-ink-200 hover:after:opacity-100' => ! $item['active'],
                           ])>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="ml-auto flex items-center gap-2 lg:gap-4">
            @auth
                <a href="{{ Navigation::url('notifications.index', '/notifications') }}"
                   class="relative grid size-control place-items-center rounded-pill text-ink-900 hover:bg-ink-100"
                   aria-label="Уведомления{{ $unread ? ': непрочитанных '.$unread : '' }}">
                    <x-icon name="bell" />
                    @if ($unread)
                        <span class="absolute top-1.5 right-1.5 grid h-5 min-w-5 place-items-center rounded-pill bg-danger-solid px-1 text-xs leading-none font-bold text-white ring-2 ring-white" aria-hidden="true">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </a>

                <details class="group relative hidden lg:block" data-dropdown>
                    <summary class="flex h-control cursor-pointer list-none items-center gap-2.5 rounded-pill py-1 pr-3 pl-1 hover:bg-ink-100 [&::-webkit-details-marker]:hidden">
                        <x-avatar :name="$user->name" />
                        <span class="max-w-40 truncate text-sm font-semibold">{{ $user->name }}</span>
                        <x-icon name="chevron-down" size="sm" class="transition-transform group-open:rotate-180" />
                    </summary>
                    <div class="absolute right-0 mt-2 w-64 rounded-md border border-subtle bg-raised p-2 shadow-md">
                        <p class="px-3 pt-2 pb-3 text-xs text-muted">{{ $user->role->label() }}</p>
                        <a href="{{ route($user->homeRoute()) }}" class="flex items-center gap-3 rounded-sm px-3 py-2.5 text-sm font-medium hover:bg-ink-100">
                            <x-icon name="user" size="sm" />Личный кабинет
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-sm px-3 py-2.5 text-left text-sm font-medium hover:bg-ink-100">
                                <x-icon name="logout" size="sm" />Выйти
                            </button>
                        </form>
                    </div>
                </details>
            @else
                <a href="{{ route('login') }}" class="hidden h-control items-center rounded-sm px-3 text-sm font-semibold hover:bg-ink-100 lg:inline-flex">Войти</a>
                <div class="hidden lg:block">
                    <x-button variant="donate" :href="Navigation::helpUrl()" icon="heart">Помочь</x-button>
                </div>
            @endauth

            <a href="{{ $nav[0]['href'] }}" class="grid size-control place-items-center rounded-pill hover:bg-ink-100 lg:hidden" aria-label="Найти питомца">
                <x-icon name="search" />
            </a>
            <button type="button" data-modal-open="mobile-menu" aria-controls="mobile-menu" aria-haspopup="dialog"
                    class="-mr-2 grid size-control place-items-center rounded-pill hover:bg-ink-100 lg:hidden" aria-label="Открыть меню">
                <x-icon name="menu" size="lg" />
            </button>
        </div>
    </div>
</header>
