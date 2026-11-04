@use('App\Support\Navigation')
{{-- Мобильное меню: полноэкранный лист на <dialog> (UI-кит, артборд 05). Открывается кнопкой в шапке. --}}
@php($user = auth()->user())
<dialog id="mobile-menu" aria-label="Меню"
        class="vm-sheet m-0 h-dvh max-h-none w-full max-w-none bg-white p-0 text-primary backdrop:bg-transparent lg:hidden">
    <div class="flex min-h-full flex-col px-4 pb-6">
        <div class="flex h-header items-center justify-between">
            <x-logo />
            <button type="button" data-modal-close class="-mr-2 grid size-control place-items-center rounded-pill hover:bg-ink-100" aria-label="Закрыть меню">
                <x-icon name="x" size="lg" />
            </button>
        </div>

        <nav aria-label="Основные разделы" class="mt-2">
            <ul class="divide-y divide-subtle border-b border-subtle">
                @foreach (Navigation::main() as $item)
                    <li>
                        <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif
                           class="flex items-center gap-4 py-4 font-display text-lg font-semibold tracking-display aria-[current=page]:text-tag-700">
                            <x-icon :name="$item['icon']" size="lg" class="text-ink-700" />{{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @auth
            <div class="mt-6 flex items-center gap-3">
                <x-avatar :name="$user->name" size="lg" />
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $user->name }}</p>
                    <p class="text-sm text-muted">{{ $user->role->label() }}</p>
                </div>
            </div>
        @endauth

        <div class="mt-auto grid gap-3 pt-8">
            <x-button variant="donate" size="lg" :href="Navigation::helpUrl()" icon="heart" block>Помочь</x-button>
            @auth
                <x-button variant="secondary" size="lg" :href="route($user->homeRoute())" icon="user" block>Личный кабинет</x-button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button variant="ghost" size="lg" icon="logout" block>Выйти</x-button>
                </form>
            @else
                <x-button variant="secondary" size="lg" :href="route('login')" block>Войти</x-button>
            @endauth
        </div>
    </div>
</dialog>
