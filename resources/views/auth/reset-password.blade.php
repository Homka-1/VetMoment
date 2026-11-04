@extends('layouts.stub')

@section('title', 'Новый пароль')

@section('content')
    <form method="POST" action="{{ route('password.update') }}" class="grid gap-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label class="grid gap-1">
            <span>Эл. почта</span>
            <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>Новый пароль</span>
            <input type="password" name="password" required minlength="8" autofocus autocomplete="new-password" class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>Повторите пароль</span>
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="rounded border p-2">
        </label>
        <button type="submit" class="rounded border p-2 font-bold">Сменить пароль</button>
    </form>
@endsection
