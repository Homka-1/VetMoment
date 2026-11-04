{{-- Спрайт иконок (design/icons/icons.svg → resources/svg/icons.svg) встраивается в страницу один раз:
     <x-icon> ссылается на символы через <use href="#i-…">, без запросов к серверу и с работой через file:// (этап 15). --}}
{!! file_get_contents(resource_path('svg/icons.svg')) !!}
