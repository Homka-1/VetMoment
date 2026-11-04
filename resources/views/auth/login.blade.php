@extends('layouts.stub')

@section('title', 'Вход')

@section('content')
    <form method="POST" action="{{ route('login.store') }}" class="grid gap-4">
        @csrf
        <label class="grid gap-1">
            <span>Логин или телефон</span>
            <input type="text" name="identifier" value="{{ old('identifier') }}" required autofocus
                   autocomplete="username" class="rounded border p-2" @error('identifier') aria-invalid="true" @enderror>
        </label>
        <label class="grid gap-1">
            <span>Пароль</span>
            <input type="password" name="password" required autocomplete="current-password" class="rounded border p-2">
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Запомнить меня</span>
        </label>
        <button type="submit" class="rounded border p-2 font-bold">Войти</button>
    </form>

    <p class="mt-6"><a href="{{ route('password.request') }}" class="underline">Забыли пароль?</a></p>
    <p class="mt-2">Нет аккаунта? <a href="{{ route('register') }}" class="underline">Зарегистрироваться</a></p>
@endsection
