@use('App\Support\Navigation')
{{-- «Как помочь»: три пути, частые вопросы и как приюту стать партнёром. --}}
<x-layouts.app title="Как помочь" description="Как помочь бездомным животным: забрать питомца, пожертвовать на сбор или погулять с собакой в приюте.">
    <div class="page-container pt-6 pb-16 lg:pt-8 lg:pb-24">
        <x-breadcrumbs :items="['Главная' => route('home'), 'Как помочь' => null]" />

        <h1 class="type-h1 mt-6">Как помочь</h1>
        <p class="type-lead mt-4 max-w-2xl text-secondary">
            Помогать можно по-разному: домом, деньгами или свободным часом. Выберите то, что подходит вам сейчас.
        </p>

        <div class="mt-10 grid gap-4 md:grid-cols-3 md:gap-6">
            <x-help-path path="adopt" title="Забрать домой" :href="route('pets.index')" action="Смотреть питомцев" heading-level="2">
                Выберите питомца и оставьте заявку. Приют перезвонит, расскажет о характере и здоровье и поможет с первой встречей. Если брать домой пока рано, можно взять питомца под опеку.
            </x-help-path>
            <x-help-path path="donate" title="Пожертвовать" :href="route('campaigns.index')" action="Выбрать сбор" heading-level="2">
                Любая сумма от 200 ₸, без регистрации. Деньги получает приют, а по каждому сбору публикует отчёт с чеками.
            </x-help-path>
            <x-help-path path="walk" title="Погулять с собакой" :href="route('walks.index')" action="Найти время" heading-level="2">
                Запишитесь на свободное время в приюте рядом. Прогулка занимает около часа, приют подтверждает запись и подскажет, как добраться.
            </x-help-path>
        </div>

        <section class="mt-16 max-w-3xl" aria-labelledby="faq-heading">
            <h2 id="faq-heading" class="type-h2">Частые вопросы</h2>
            <div class="mt-6 divide-y divide-subtle border-y border-subtle">
                @foreach ([
                    ['Нужно ли регистрироваться, чтобы пожертвовать?', 'Нет. Достаточно выбрать сбор, указать сумму и email. Регистрация нужна, чтобы записываться на выгул, оставлять заявки и видеть историю.'],
                    ['Куда попадают деньги?', 'Напрямую приюту, который открыл сбор. Площадка не удерживает комиссию из пожертвования. Когда сбор закрыт, приют публикует отчёт о расходах.'],
                    ['С какого возраста можно гулять с собаками?', 'С 18 лет. Некоторые приюты просят взять документ и пройти первую прогулку с куратором — правила приюта указаны на его странице и при записи.'],
                    ['Что, если я не смогу прийти на выгул?', 'Отмените запись в кабинете не позже чем за 3 часа до начала — место достанется другому волонтёру. Если времени меньше, позвоните в приют.'],
                    ['Как работает опека?', 'Питомец остаётся в приюте, а вы помогаете ему регулярно: деньгами на корм и лечение, визитами, поиском хозяев. Опека доступна для тех питомцев, у которых приют её разрешил.'],
                ] as [$question, $answer])
                    <details class="group py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold [&::-webkit-details-marker]:hidden">
                            {{ $question }}
                            <x-icon name="chevron-down" class="shrink-0 transition-transform duration-200 group-open:rotate-180" />
                        </summary>
                        <p class="mt-3 max-w-[65ch] text-secondary">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="mt-16 rounded-lg bg-canvas p-6 md:p-10" aria-labelledby="partner-heading">
            <h2 id="partner-heading" class="type-h3">Вы представляете приют?</h2>
            <p class="mt-3 max-w-2xl text-secondary">
                Зарегистрируйтесь, подайте заявку и после проверки публикуйте анкеты, открывайте сборы и принимайте волонтёров на выгул. Проверка занимает 1–2 рабочих дня.
            </p>
            <x-button class="mt-6" icon="home" :href="auth()->check() ? route('cabinet.shelter-request') : route('register')">Стать приютом-партнёром</x-button>
        </section>
    </div>
</x-layouts.app>
