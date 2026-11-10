@use('App\Enums\Role')
@use('App\Support\LocalTime')
@use('App\Support\Phone')
{{-- Пользователи: поиск, фильтр по роли, блокировка. Блокировка отменяет будущие записи на выгул. --}}
<x-layouts.cabinet title="Пользователи">
    <div class="grid grid-cols-1 gap-6">
        <form method="GET" action="{{ route('admin.users.index') }}" role="search" class="grid items-end gap-3 sm:grid-cols-[1fr_14rem_auto_auto]">
            <x-input name="q" type="search" label="Поиск" :value="$search" placeholder="Имя, логин, почта или телефон" />
            <x-select name="role" label="Роль" :options="collect(Role::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" :value="$role?->value" placeholder="Все роли" />
            <x-checkbox name="blocked" value="1" :checked="$blocked" class="pb-2">Только заблокированные ({{ $blockedCount }})</x-checkbox>
            <x-button variant="secondary">Найти</x-button>
        </form>

        @if ($users->isEmpty())
            <x-empty-state title="Никого не нашли" illustration="cat-smoke" tone="sky">Попробуйте другое имя, логин или снимите фильтры.</x-empty-state>
        @else
            <x-table caption="Пользователи">
                <x-slot:head>
                    <tr>
                        <x-table.th>Пользователь</x-table.th>
                        <x-table.th>Контакты</x-table.th>
                        <x-table.th>Роль</x-table.th>
                        <x-table.th>Регистрация</x-table.th>
                        <x-table.th><span class="sr-only">Действия</span></x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($users as $user)
                    <tr>
                        <td class="min-w-48">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$user->name" />
                                <div>
                                    <p class="font-semibold">{{ $user->name }}</p>
                                    <p class="text-xs text-secondary">{{ $user->login }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="min-w-48"><p>{{ Phone::format($user->phone) }}</p><p class="text-xs text-secondary">{{ $user->email }}</p></td>
                        <td>
                            {{ $user->role->label() }}
                            @if ($user->shelters->first())<p class="text-xs text-secondary">{{ $user->shelters->first()->name }}</p>@endif
                            @if ($user->is_blocked)<p class="mt-1"><x-badge tone="danger">Заблокирован</x-badge></p>@endif
                        </td>
                        <td class="whitespace-nowrap">{{ LocalTime::date($user->created_at) }}</td>
                        <td class="text-right">
                            @can('block', $user)
                                <x-button variant="secondary" size="sm" type="button" data-modal-open="block-user-{{ $user->id }}">Заблокировать</x-button>
                            @endcan
                            @can('unblock', $user)
                                <form method="POST" action="{{ route('admin.users.unblock', $user) }}">@csrf<x-button variant="secondary" size="sm">Разблокировать</x-button></form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-table>
            <x-pagination :paginator="$users" />
        @endif
    </div>

    @foreach ($users as $user)
        @can('block', $user)
            <x-modal :id="'block-user-'.$user->id" title="Заблокировать пользователя?">
                <p>«{{ $user->login }}» не сможет войти, а его будущие записи на выгул будут отменены.</p>
                <x-slot:footer>
                    <x-button variant="ghost" type="button" data-modal-close>Отмена</x-button>
                    <form method="POST" action="{{ route('admin.users.block', $user) }}">@csrf<x-button variant="danger">Заблокировать</x-button></form>
                </x-slot:footer>
            </x-modal>
        @endcan
    @endforeach
</x-layouts.cabinet>
