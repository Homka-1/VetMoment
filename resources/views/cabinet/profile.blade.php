@use('App\Support\Phone')
{{-- Профиль: контакты и смена пароля. Логин не меняется — по нему входят. --}}
<x-layouts.cabinet title="Профиль">
    <div class="grid max-w-narrow gap-6">
        <x-form-errors />

        <x-card padding="lg">
            <h2 class="type-h4">Контакты</h2>
            <p class="mt-1 text-sm text-secondary">Логин для входа: <strong class="font-semibold text-primary">{{ $user->login }}</strong>. Телефон видят приюты, в которые вы записались или подали заявку.</p>
            <form method="POST" action="{{ route('cabinet.profile.update') }}" class="mt-6 grid gap-5" novalidate>
                @csrf
                @method('PATCH')
                <x-input name="name" label="Имя и фамилия" :value="$user->name" required maxlength="100" autocomplete="name" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="phone" type="tel" label="Телефон" :value="Phone::format($user->phone)" required inputmode="tel" autocomplete="tel" placeholder="+7 701 234 56 78" />
                    <x-input name="email" type="email" label="Эл. почта" :value="$user->email" required autocomplete="email" />
                </div>
                <div><x-button>Сохранить</x-button></div>
            </form>
        </x-card>

        <x-card padding="lg">
            <h2 class="type-h4">Пароль</h2>
            <form method="POST" action="{{ route('cabinet.profile.password') }}" class="mt-6 grid gap-5" novalidate>
                @csrf
                @method('PUT')
                <x-input name="current_password" type="password" label="Текущий пароль" required autocomplete="current-password" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="password" type="password" label="Новый пароль" required autocomplete="new-password" hint="Не короче 8 символов" />
                    <x-input name="password_confirmation" type="password" label="Ещё раз новый пароль" required autocomplete="new-password" />
                </div>
                <div><x-button variant="secondary">Изменить пароль</x-button></div>
            </form>
        </x-card>
    </div>
</x-layouts.cabinet>
