@use('App\Support\Navigation')
{{-- Главная (макет 01). На этапе 7 — статичные блоки: герой и три пути помощи.
     Блоки с данными (питомцы, сборы, слоты, приюты, цифры) подключаются после этапов 8–10. --}}
<x-layouts.app>
    <section class="page-container grid items-center gap-10 pt-10 pb-16 md:pt-14 lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:pt-20 lg:pb-24">
        <div>
            <h1 class="type-h1 max-w-[12ch]">У каждого здесь есть имя</h1>
            <p class="type-lead mt-6 max-w-[34rem] text-secondary">
                Анкеты питомцев из приютов Казахстана. Заберите друга домой, помогите сбору на лечение
                или приходите погулять с собакой в свободный день.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <x-button size="lg" :href="Navigation::url('pets.index', '/pets')" icon="search">Найти питомца</x-button>
                <x-button size="lg" variant="donate" :href="Navigation::url('campaigns.index', '/campaigns')" icon="heart">Помочь деньгами</x-button>
            </div>
            <p class="mt-6 flex items-center gap-2 text-sm text-secondary">
                <x-icon name="shield-check" size="sm" class="text-success-fg" />Все приюты проходят проверку перед публикацией
            </p>
        </div>

        {{-- Портрет с адресной биркой — главный мотив айдентики «Бирка на ошейнике» --}}
        <div class="relative mx-auto w-full max-w-[28rem]" aria-hidden="true">
            <div class="relative aspect-square overflow-hidden rounded-pill bg-sky-100">
                <span class="absolute top-[12%] right-[10%] size-[18%] rounded-pill bg-tag-200"></span>
                <img src="{{ asset('images/illustrations/dog-caramel.svg') }}" alt="" class="absolute inset-x-0 bottom-0 mx-auto h-[88%] w-auto">
            </div>
            <div class="absolute right-0 bottom-[6%] grid size-[38%] -rotate-12 place-content-center rounded-pill bg-tag-500 text-center text-ink-900 shadow-md sm:right-[2%]">
                <span class="mx-auto mb-2 size-3 rounded-pill border-2 border-ink-900 bg-white"></span>
                <span class="font-display text-lg font-semibold tracking-display sm:text-xl">Бублик</span>
                <span class="mt-1 text-xs font-semibold">2 года, ищет дом</span>
            </div>
        </div>
    </section>

    <section class="page-container pb-16 lg:pb-24" aria-labelledby="help-heading">
        <h2 id="help-heading" class="type-h2">Как помочь</h2>
        <div class="mt-8 grid gap-4 md:grid-cols-3 md:gap-6">
            <x-help-path path="adopt" title="Забрать домой" :href="Navigation::url('pets.index', '/pets')" action="Смотреть питомцев">
                Выберите питомца и оставьте заявку. Приют перезвонит и расскажет, как познакомиться.
            </x-help-path>
            <x-help-path path="donate" title="Пожертвовать" :href="Navigation::url('campaigns.index', '/campaigns')" action="Выбрать сбор">
                Любая сумма от 200 ₸, без регистрации. По каждому сбору приют публикует отчёт.
            </x-help-path>
            <x-help-path path="walk" title="Погулять с собакой" :href="Navigation::url('walks.index', '/walks')" action="Найти время">
                Запишитесь на свободное время в приюте рядом. Прогулка занимает около часа.
            </x-help-path>
        </div>
    </section>
</x-layouts.app>
