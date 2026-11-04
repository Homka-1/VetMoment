<x-layouts.auth title="Восстановление пароля" heading="Забыли пароль?" illustration="dog-graphite"
                aside-title="Граф подождёт" aside-text="Ссылка для смены пароля действует 60 минут. Если письма нет — проверьте папку «Спам».">
    <x-slot:subtitle>Укажите почту, с которой регистрировались, — пришлём ссылку для смены пароля.</x-slot:subtitle>

    <form method="POST" action="{{ route('password.email') }}" class="grid gap-5" novalidate>
        @csrf
        <x-input name="email" type="email" label="Эл. почта" required autofocus autocomplete="email" placeholder="name@mail.kz" />
        <x-button size="lg" block>Отправить ссылку</x-button>
    </form>

    <p class="mt-6 text-sm"><a href="{{ route('login') }}" class="link">Вернуться ко входу</a></p>
</x-layouts.auth>
