<x-layouts.auth title="Регистрация" illustration="dog-caramel" aside-title="Бублик ждёт прогулку"
                aside-text="С аккаунтом можно записываться на выгул, подавать заявки на питомцев и видеть историю пожертвований.">
    <x-slot:subtitle>
        Уже есть аккаунт? <a href="{{ route('login') }}" class="link">Войти</a>
    </x-slot:subtitle>

    <form method="POST" action="{{ route('register.store') }}" class="grid gap-5" novalidate>
        @csrf
        <x-input name="name" label="Имя" required maxlength="100" autocomplete="name" placeholder="Как к вам обращаться"
                 hint="Увидит приют, когда вы запишетесь на выгул" />
        <x-input name="login" label="Логин" required minlength="3" maxlength="32" autocomplete="username"
                 hint="Латинские буквы, цифры, «_», «.», «-» — от 3 до 32 символов" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-input name="phone" type="tel" label="Телефон" required autocomplete="tel" inputmode="tel" placeholder="+7 701 234 56 78" />
            <x-input name="email" type="email" label="Эл. почта" required autocomplete="email" placeholder="name@mail.kz" />
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-input name="password" type="password" label="Пароль" required minlength="8" autocomplete="new-password" hint="Не короче 8 символов" />
            <x-input name="password_confirmation" type="password" label="Повторите пароль" required minlength="8" autocomplete="new-password" />
        </div>
        <x-checkbox name="agree">Согласен(на) на обработку персональных данных</x-checkbox>
        <x-button size="lg" block>Зарегистрироваться</x-button>
    </form>
</x-layouts.auth>
