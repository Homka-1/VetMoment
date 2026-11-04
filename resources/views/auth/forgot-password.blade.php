@extends('layouts.stub')

@section('title', 'Восстановление пароля')

@section('content')
    <p class="mb-4">Укажите почту, с которой регистрировались, — пришлём ссылку для смены пароля.</p>

    <form method="POST" action="{{ route('password.email') }}" class="grid gap-4">
        @csrf
        <label class="grid gap-1">
            <span>Эл. почта</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="rounded border p-2">
        </label>
        <button type="submit" class="rounded border p-2 font-bold">Отправить ссылку</button>
    </form>

    <p class="mt-6"><a href="{{ route('login') }}" class="underline">Вернуться ко входу</a></p>
@endsection
