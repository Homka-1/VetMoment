{{-- 403: раздел другой роли или действие, которое этой учётной записи недоступно. --}}
@php($user = auth()->user())
<x-error-page code="403" title="Этот раздел вам недоступен" illustration="cat-ginger">
    @if ($user)
        Страница открыта только для другой роли — например, сотрудникам приюта или администратору.
        Если доступ нужен по работе, попросите владельца приюта или администратора.
    @else
        Страница открыта только после входа. Войдите — и мы вернём вас сюда.
    @endif
    <x-slot:actions>
        @if ($user)
            <x-button :href="route($user->homeRoute())">В личный кабинет</x-button>
        @else
            <x-button :href="route('login')">Войти</x-button>
        @endif
        <x-button variant="secondary" :href="route('home')">На главную</x-button>
    </x-slot:actions>
</x-error-page>
