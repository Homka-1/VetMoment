<x-layouts.auth title="Новый пароль" illustration="dog-graphite"
                aside-title="Почти готово" aside-text="После смены пароля войдите с новым — по логину или телефону.">
    <form method="POST" action="{{ route('password.update') }}" class="grid gap-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-input name="email" type="email" label="Эл. почта" :value="$email" required autocomplete="email" />
        <x-input name="password" type="password" label="Новый пароль" required minlength="8" autofocus autocomplete="new-password" hint="Не короче 8 символов" />
        <x-input name="password_confirmation" type="password" label="Повторите пароль" required minlength="8" autocomplete="new-password" />
        <x-button size="lg" block>Сменить пароль</x-button>
    </form>
</x-layouts.auth>
