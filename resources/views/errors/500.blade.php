{{-- 500 (макет 12-states): без сессии и БД в шапке — страница должна открыться, даже если упала база. --}}
<x-error-page code="500" title="Сайт временно не отвечает" illustration="cat-smoke" guest>
    Мы уже чиним. Если вы оплачивали пожертвование, деньги не потеряются: квитанция придёт на почту.
    <x-slot:actions>
        <x-button :href="request()->fullUrl()">Обновить страницу</x-button>
        <x-button variant="secondary" :href="url('/')">На главную</x-button>
    </x-slot:actions>
</x-error-page>
