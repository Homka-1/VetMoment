@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Настройки профиля</h2>
        <p class="text-gray-500 mt-2 text-sm">Обновите ваши контактные данные</p>
    </div>

    <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div>
            <label for="login" class="block text-sm font-medium text-gray-700 mb-1">Ваш логин</label>
            <input type="text" name="login" id="login" value="{{ old('login', $user->login) }}" required class="w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2.5 border bg-gray-50">
            @error('login') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Номер телефона</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" required class="w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2.5 border bg-gray-50">
            <p class="text-xs text-gray-500 mt-1">Телефон будет показан только тому пользователю, чью заявку вы примете, или тому, кто примет вашу.</p>
            @error('phone') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="pt-4 border-t border-gray-100">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Смена пароля (необязательно)</h3>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
            <input type="password" name="password" id="password" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2.5 border bg-gray-50" placeholder="Оставьте пустым, если не хотите менять">
            @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-medium py-3 px-4 rounded-xl shadow-sm transition-colors mt-4">
            Сохранить изменения
        </button>
    </form>
</div>
@endsection
