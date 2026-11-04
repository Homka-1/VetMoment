@extends('layouts.stub')

@section('title', $shelter ? 'Исправить заявку приюта' : 'Стать приютом-партнёром')

@section('content')
    @if ($shelter?->moderation_comment)
        <p class="mb-4 rounded border p-3">Причина отказа: {{ $shelter->moderation_comment }}</p>
    @endif

    <form method="POST" action="{{ route('cabinet.shelter-request.store') }}" enctype="multipart/form-data" class="grid gap-4">
        @csrf
        <label class="grid gap-1">
            <span>Название приюта</span>
            <input type="text" name="name" value="{{ old('name', $shelter?->name) }}" required maxlength="150" class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>Город</span>
            <input type="text" name="city" value="{{ old('city', $shelter?->city) }}" required maxlength="100" class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>Адрес</span>
            <input type="text" name="address" value="{{ old('address', $shelter?->address) }}" required maxlength="255" class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>Телефон приюта</span>
            <input type="tel" name="phone" value="{{ old('phone', $shelter ? \App\Support\Phone::format($shelter->phone) : '') }}" required
                   placeholder="+7 727 123 45 67" class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>Эл. почта приюта</span>
            <input type="email" name="email" value="{{ old('email', $shelter?->email) }}" required class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>Описание</span>
            <textarea name="description" required minlength="50" maxlength="5000" rows="6" class="rounded border p-2"
                      aria-describedby="description-hint">{{ old('description', $shelter?->description) }}</textarea>
            <small id="description-hint">Кто вы, сколько животных, чем можно помочь. От 50 символов.</small>
        </label>
        <label class="grid gap-1">
            <span>Сайт или соцсети (необязательно)</span>
            <input type="url" name="website" value="{{ old('website', $shelter?->website) }}" maxlength="255" class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>БИН / ИИН (необязательно)</span>
            <input type="text" name="bin_iin" value="{{ old('bin_iin', $shelter?->bin_iin) }}" inputmode="numeric" maxlength="14" class="rounded border p-2">
        </label>
        <label class="grid gap-1">
            <span>Логотип (jpg, png, webp до 2 МБ, необязательно)</span>
            <input type="file" name="logo" accept="image/jpeg,image/png,image/webp">
        </label>
        <label class="flex items-start gap-2">
            <input type="checkbox" name="agree" value="1" required @checked(old('agree'))>
            <span>Согласен(на) с правилами площадки и обработкой персональных данных</span>
        </label>
        <button type="submit" class="rounded border p-2 font-bold">Отправить на модерацию</button>
    </form>
@endsection
