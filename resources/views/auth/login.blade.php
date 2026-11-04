<x-layouts.auth title="Вход">
    <x-slot:subtitle>
        Нет аккаунта? <a href="{{ route('register') }}" class="link">Зарегистрироваться</a>
    </x-slot:subtitle>

    <form method="POST" action="{{ route('login.store') }}" class="grid gap-5" novalidate>
        @csrf
        <x-input name="identifier" label="Логин или телефон" required autofocus autocomplete="username"
                 placeholder="+7 701 234 56 78" />
        <x-input name="password" type="password" label="Пароль" required autocomplete="current-password">
            <x-slot:aside><a href="{{ route('password.request') }}" class="link text-sm">Забыли пароль?</a></x-slot:aside>
        </x-input>
        <x-checkbox name="remember">Запомнить меня</x-checkbox>
        <x-button size="lg" block>Войти</x-button>
    </form>
</x-layouts.auth>
