{{-- 503: режим обслуживания (php artisan down) или перезапуск. Без сессии и БД. --}}
<x-error-page code="503" title="Идут технические работы" illustration="dog-graphite" guest>
    Сайт вернётся через несколько минут. Если вы оплачивали пожертвование, деньги не потеряются: квитанция придёт на почту.
    <x-slot:actions>
        <x-button :href="request()->fullUrl()">Обновить страницу</x-button>
    </x-slot:actions>
</x-error-page>
