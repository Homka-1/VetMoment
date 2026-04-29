@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-8 sm:mt-12 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900">С возвращением!</h2>
        <p class="text-gray-500 mt-2 text-sm">Войдите, чтобы помогать животным</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <label for="login" class="block text-sm font-medium text-gray-700 mb-1">Логин</label>
            <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus class="w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2.5 border bg-gray-50">
            @error('login') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
            <input type="password" name="password" id="password" required class="w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2.5 border bg-gray-50">
            @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-medium py-3 px-4 rounded-xl shadow-sm transition-colors mt-2">
            Войти в систему
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-gray-600">
        Нет аккаунта? <a href="{{ route('register') }}" class="text-emerald-600 font-medium hover:text-emerald-700">Зарегистрироваться</a>
    </div>
</div>
@endsection
