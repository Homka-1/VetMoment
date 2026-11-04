@extends('layouts.stub')

@section('title', $title)

@section('content')
    <p>Вы вошли как <strong>{{ $user->name }}</strong> ({{ $user->login }}) — {{ $user->role->label() }}.</p>

    @isset($shelter)
        <p class="mt-4">Приют: <strong>{{ $shelter->name }}</strong> — {{ $shelter->status->label() }}.</p>
        @if ($shelter->status === \App\Enums\ShelterStatus::Pending)
            <p class="mt-2">Заявка на модерации: можно готовить черновики, публикация — после одобрения.</p>
        @elseif ($shelter->status === \App\Enums\ShelterStatus::Rejected)
            <p class="mt-2">Заявка отклонена: {{ $shelter->moderation_comment }}</p>
            <p class="mt-2"><a href="{{ route('cabinet.shelter-request') }}" class="underline">Исправить и отправить снова</a></p>
        @endif
    @endisset

    @can('register', \App\Models\Shelter::class)
        <p class="mt-4"><a href="{{ route('cabinet.shelter-request') }}" class="underline">Стать приютом-партнёром</a></p>
    @endcan

    <p class="mt-4 text-sm">Разделы кабинета появятся на этапе 11.</p>

    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf
        <button type="submit" class="rounded border px-4 py-2">Выйти</button>
    </form>
@endsection
