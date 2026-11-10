@use('App\Support\LocalTime')
@use('App\Support\Money')
@use('App\Support\Navigation')
@use('App\Support\Plural')
{{-- Главная (макет 01): герой, три пути помощи, питомцы, сборы, выгулы недели, приюты-партнёры, цифры проекта. --}}
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

    @if ($pets->isNotEmpty())
        <section class="page-container pb-16 lg:pb-24" aria-labelledby="pets-heading">
            <div class="flex items-baseline justify-between gap-4">
                <div>
                    <h2 id="pets-heading" class="type-h2">Ждут знакомства</h2>
                    <p class="mt-2 text-secondary">Дольше всех ищут дом. Подборка обновляется по мере того, как питомцев забирают.</p>
                </div>
                <a href="{{ route('pets.index') }}" class="link hidden shrink-0 items-center gap-1 sm:inline-flex">Все питомцы<x-icon name="chevron-right" size="sm" /></a>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4 lg:gap-6">
                @foreach ($pets as $pet)
                    <x-pet-card :pet="$pet" class="w-full" responsive heading-level="3" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($campaigns->isNotEmpty())
        <section class="bg-canvas py-16 lg:py-24" aria-labelledby="campaigns-heading">
            <div class="page-container">
                <div class="flex items-baseline justify-between gap-4">
                    <div>
                        <h2 id="campaigns-heading" class="type-h2">Сборы сейчас</h2>
                        <p class="mt-2 text-secondary">Деньги идут прямо приюту. После сбора приют публикует отчёт с чеками.</p>
                    </div>
                    <a href="{{ route('campaigns.index') }}" class="link hidden shrink-0 items-center gap-1 sm:inline-flex">Все сборы<x-icon name="chevron-right" size="sm" /></a>
                </div>
                <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                    @foreach ($campaigns as $campaign)
                        <x-campaign-card :campaign="$campaign" class="w-full" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($slots->isNotEmpty())
        <section class="page-container py-16 lg:py-24" aria-labelledby="walks-heading">
            <div class="flex items-baseline justify-between gap-4">
                <h2 id="walks-heading" class="type-h2">Можно погулять на этой неделе</h2>
                <a href="{{ route('walks.index') }}" class="link hidden shrink-0 items-center gap-1 sm:inline-flex">Выбрать время<x-icon name="chevron-right" size="sm" /></a>
            </div>
            <ul class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-7">
                @foreach ($slots as $slot)
                    <li>
                        <p class="text-sm font-semibold">{{ LocalTime::of($slot->starts_at)->isToday() ? 'Сегодня' : Str::ucfirst(LocalTime::weekdayShort($slot->starts_at)).', '.LocalTime::date($slot->starts_at) }}</p>
                        <p class="mb-2 truncate text-xs text-secondary">{{ $slot->shelter->name }}</p>
                        <x-slot-tile :model="$slot" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($shelters->isNotEmpty())
        <section class="page-container pb-16 lg:pb-24" aria-labelledby="shelters-heading">
            <div class="flex items-baseline justify-between gap-4">
                <h2 id="shelters-heading" class="type-h2">Приюты-партнёры</h2>
                <a href="{{ route('shelters.index') }}" class="link hidden shrink-0 items-center gap-1 sm:inline-flex">Все приюты<x-icon name="chevron-right" size="sm" /></a>
            </div>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
                @foreach ($shelters as $shelter)
                    <x-shelter-card :shelter="$shelter" :pets-count="$shelter->pets_count" :campaigns-count="null" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="page-container pb-16 lg:pb-24" aria-labelledby="stats-heading">
        <div class="rounded-lg bg-path-donate p-6 md:p-10">
            <h2 id="stats-heading" class="font-semibold">Что получилось вместе</h2>
            <dl class="mt-6 grid gap-8 sm:grid-cols-3">
                <div class="flex flex-col"><dd class="type-number order-first">{{ number_format($stats['adopted'], 0, ',', Money::NBSP) }}</dd><dt class="mt-1 text-secondary">{{ Plural::choose($stats['adopted'], ['питомец нашёл дом', 'питомца нашли дом', 'питомцев нашли дом']) }}</dt></div>
                <div class="flex flex-col"><dd class="type-number order-first">{{ Money::compact($stats['raised']) }}</dd><dt class="mt-1 text-secondary">собрано на лечение и корм</dt></div>
                <div class="flex flex-col"><dd class="type-number order-first">{{ number_format($stats['walks'], 0, ',', Money::NBSP) }}</dd><dt class="mt-1 text-secondary">{{ Plural::choose($stats['walks'], ['прогулка', 'прогулки', 'прогулок']) }} с волонтёрами</dt></div>
            </dl>
        </div>
    </section>
</x-layouts.app>
