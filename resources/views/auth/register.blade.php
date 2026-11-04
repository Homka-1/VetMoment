@extends('layouts.stub')

@section('title', 'Регистрация')

@section('content')
    <form method="POST" action="{{ route('register.store') }}" class="grid gap-4">
        @csrf
        <label class="grid gap-1">
            <span>Логин</span>
            <input type="text" name="login" value="{{ old('login') }}" required minlength="3" maxlength="32"
                   autocomplete="username" class="rounded border p-2" aria-describedby="login-hint">
            <small id="login-hint">Латинские буквы, цифры, «_», «.», «-»; от 3 до 32 символов.</small>
        </label>
        <label class="grid gap-1">
            <span>Имя</span>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name" class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>Телефон</span>
            <input type="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel"
                   placeholder="+7 701 234 56 78" class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>Эл. почта</span>
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>Пароль</span>
            <input type="password" name="password" required minlength="8" autocomplete="new-password" class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>Повторите пароль</span>
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="rounded border p-2">
        </label>
        <label class="flex items-start gap-2">
            <input type="checkbox" name="agree" value="1" required @checked(old('agree'))>
            <span>Согласен(на) на обработку персональных данных</span>
        </label>
        <button type="submit" class="rounded border p-2 font-bold">Зарегистрироваться</button>
    </form>

    <p class="mt-6">Уже есть аккаунт? <a href="{{ route('login') }}" class="underline">Войти</a></p>
@endsection
