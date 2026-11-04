@use('App\Enums\WalkSlotState')
{{-- Витрина UI-кита (только local/testing). Разделы повторяют артборды design/ui-kit.svg. --}}
<x-layouts.app title="Витрина UI-кита">
    @php
        $sections = [
            'foundations' => 'Основы',
            'buttons' => 'Кнопки',
            'fields' => 'Поля ввода',
            'choice' => 'Выбор',
            'filters' => 'Фильтры и суммы',
            'statuses' => 'Статусы',
            'slots' => 'Слоты выгула',
            'cards' => 'Карточки',
            'navigation' => 'Навигация',
            'feedback' => 'Отклик',
            'data' => 'Таблица и галерея',
            'icons' => 'Иконки',
        ];
        $swatches = [
            'ink' => ['950', '900', '800', '700', '600', '500', '400', '300', '200', '100', '50'],
            'tag' => ['700', '600', '500', '200', '100'],
            'sky' => ['700', '600', '500', '200', '100'],
            'meadow' => ['700', '600', '500', '200', '100'],
        ];
        $sg = 'scroll-mt-28 border-t border-subtle pt-12 pb-16';
    @endphp

    <div class="page-container py-10 lg:py-14">
        <x-breadcrumbs :items="['Главная' => route('home'), 'Витрина UI-кита' => null]" />
        <h1 class="type-h1 mt-6">Витрина UI-кита</h1>
        <p class="type-lead mt-4 max-w-prose text-secondary">
            Все Blade-компоненты из <code class="rounded-xs bg-ink-100 px-1.5 text-base">resources/views/components</code> на одной странице.
            Сверяйте с артбордами <code class="rounded-xs bg-ink-100 px-1.5 text-base">design/ui-kit.svg</code>. Страница доступна только в local-окружении.
        </p>

        <nav aria-label="Разделы витрины" class="mt-8 flex flex-wrap gap-2">
            @foreach ($sections as $id => $label)
                <x-chip :href="'#'.$id">{{ $label }}</x-chip>
            @endforeach
        </nav>
    </div>

    <div class="page-container">
        {{-- ========== Основы ========== --}}
        <section id="foundations" class="{{ $sg }}" aria-labelledby="foundations-h">
            <h2 id="foundations-h" class="type-h2">Основы</h2>
            <p class="mt-3 max-w-prose text-secondary">Палитра, шрифты, радиусы и тени — токены из <code>design/tokens.json</code> в <code>@theme</code>. Палитра Tailwind по умолчанию отключена.</p>

            <div class="mt-8 grid gap-6">
                @foreach ($swatches as $scale => $steps)
                    <div>
                        <h3 class="type-label">{{ $scale }}</h3>
                        <ul class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6 lg:grid-cols-11">
                            @foreach ($steps as $step)
                                <li class="grid gap-1.5">
                                    <span class="h-14 rounded-sm border border-subtle bg-{{ $scale }}-{{ $step }}"></span>
                                    <span class="type-caption text-muted">{{ $scale }}-{{ $step }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
                {{-- Классы для сканера Tailwind: bg-ink-950 bg-ink-900 bg-ink-800 bg-ink-700 bg-ink-600 bg-ink-500 bg-ink-400 bg-ink-300 bg-ink-200 bg-ink-100 bg-ink-50
                     bg-tag-700 bg-tag-600 bg-tag-500 bg-tag-200 bg-tag-100 bg-sky-700 bg-sky-600 bg-sky-500 bg-sky-200 bg-sky-100
                     bg-meadow-700 bg-meadow-600 bg-meadow-500 bg-meadow-200 bg-meadow-100 --}}
            </div>

            <div class="mt-10 grid gap-8 lg:grid-cols-2">
                <div class="grid gap-4">
                    <p class="type-h1">Заголовок h1 — Unbounded 49</p>
                    <p class="type-h2">Заголовок h2 — 39</p>
                    <p class="type-h3">Заголовок h3 — 25</p>
                    <p class="type-h4">Заголовок h4 — 20</p>
                    <p class="type-number">412 500 ₸</p>
                </div>
                <div class="grid content-start gap-3">
                    <p class="type-lead">Lead 18 — Golos Text. Анкеты питомцев из приютов Казахстана.</p>
                    <p>Body 16 — основной текст интерфейса, длина строки до 75 знаков. Қазақша: әңғүұқөһі.</p>
                    <p class="text-sm">Small 14 — подписи и метаданные карточек.</p>
                    <p class="type-label">Label 14 — подпись поля</p>
                    <p class="type-caption text-muted">Caption 12 — подсказка под полем</p>
                    <p><a href="#foundations" class="link">Ссылка в тексте всегда подчёркнута</a></p>
                </div>
            </div>

            <div class="mt-10 flex flex-wrap gap-6">
                @foreach (['xs' => '6', 'sm' => '10', 'md' => '16', 'lg' => '24', 'pill' => '999'] as $r => $px)
                    <div class="grid justify-items-center gap-2">
                        <span class="size-20 rounded-{{ $r }} border-2 border-ink-900 bg-tag-100"></span>
                        <span class="type-caption text-muted">rounded-{{ $r }} · {{ $px }}</span>
                    </div>
                @endforeach
                {{-- rounded-xs rounded-sm rounded-md rounded-lg rounded-pill --}}
                @foreach (['sm', 'md', 'lg'] as $s)
                    <div class="grid justify-items-center gap-2">
                        <span class="size-20 rounded-md bg-white shadow-{{ $s }}"></span>
                        <span class="type-caption text-muted">shadow-{{ $s }}</span>
                    </div>
                @endforeach
                {{-- shadow-sm shadow-md shadow-lg --}}
            </div>
        </section>

        {{-- ========== Кнопки ========== --}}
        <section id="buttons" class="{{ $sg }}" aria-labelledby="buttons-h">
            <h2 id="buttons-h" class="type-h2">Кнопки</h2>
            <p class="mt-3 max-w-prose text-secondary">Подпись говорит, что произойдёт. Жёлтая кнопка — только для пожертвований.</p>

            <div class="mt-8 overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="text-xs text-muted">
                        <tr><th class="pb-3 font-semibold">Вид</th><th class="pb-3 font-semibold">Обычная</th><th class="pb-3 font-semibold">Недоступна</th><th class="pb-3 font-semibold">Загрузка</th></tr>
                    </thead>
                    <tbody>
                        @foreach (['primary' => 'Записаться', 'donate' => 'Помочь сбору', 'secondary' => 'Хочу забрать', 'ghost' => 'Подробнее', 'danger' => 'Отменить запись'] as $variant => $text)
                            <tr>
                                <th class="py-2 pr-4 font-semibold">{{ $variant }}</th>
                                <td class="py-2 pr-4"><x-button :variant="$variant" type="button">{{ $text }}</x-button></td>
                                <td class="py-2 pr-4"><x-button :variant="$variant" type="button" disabled>{{ $text }}</x-button></td>
                                <td class="py-2"><x-button :variant="$variant" type="button" loading>{{ $text }}</x-button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <x-button size="sm" type="button">Маленькая</x-button>
                <x-button type="button">Средняя</x-button>
                <x-button size="lg" type="button">Большая</x-button>
                <x-button type="button" icon="leash">Записаться на выгул</x-button>
                <x-button variant="secondary" type="button" icon-right="chevron-down">Ещё питомцы</x-button>
                <x-button variant="secondary" type="button" icon="share" icon-only label="Поделиться" />
                <x-button variant="ghost" type="button" icon="heart" icon-only label="В избранное" />
                <x-button variant="secondary" href="#buttons">Кнопка-ссылка</x-button>
            </div>
        </section>

        {{-- ========== Поля ========== --}}
        <section id="fields" class="{{ $sg }}" aria-labelledby="fields-h">
            <h2 id="fields-h" class="type-h2">Поля ввода</h2>
            <p class="mt-3 max-w-prose text-secondary">Подпись всегда над полем. Ошибка — красная граница, иконка и текст, который объясняет, как исправить.</p>

            <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <x-input name="sg_name" label="Имя" placeholder="Как к вам обращаться" hint="Увидит только приют" />
                <x-input name="sg_name_filled" label="Имя" value="Айгерим" hint="Заполнено" />
                <x-input name="sg_phone" type="tel" label="Телефон" value="+7 701 234 56" required />
                <x-input name="sg_email" type="email" label="Электронная почта" value="aigerim@mail.kz" disabled hint="Нельзя изменить после оплаты" />
                <x-select name="sg_species" label="Вид" :options="\App\Enums\Species::options()" value="dog" hint="Выпадающий список" />
                <x-input name="sg_amount" label="Своя сумма" value="3 500" suffix="₸" inputmode="numeric" hint="От 200 до 5 000 000 ₸" />
                <x-input name="sg_search" type="search" label="Поиск по кличке" icon="search" placeholder="Например, Бублик" class="lg:col-span-2" />
                <x-textarea name="sg_comment" label="Комментарий для приюта" maxlength="1000" class="md:col-span-2 lg:col-span-4"
                            value="Был опыт с собаками, живу в частном доме с двором. Могу приезжать по выходным." hint="Необязательно" />
            </div>
        </section>

        {{-- ========== Выбор ========== --}}
        <section id="choice" class="{{ $sg }}" aria-labelledby="choice-h">
            <h2 id="choice-h" class="type-h2">Выбор</h2>
            <p class="mt-3 max-w-prose text-secondary">Зона клика — вся строка с подписью.</p>
            <div class="mt-8 grid gap-8 md:grid-cols-3">
                <div class="grid content-start gap-1">
                    <x-checkbox name="sg_kids">Ладит с детьми</x-checkbox>
                    <x-checkbox name="sg_vaccinated" checked>Привит</x-checkbox>
                    <x-checkbox name="sg_disabled" disabled>Недоступно</x-checkbox>
                    <x-checkbox name="sg_disabled_checked" checked disabled>Недоступно, выбрано</x-checkbox>
                </div>
                <fieldset class="grid content-start gap-1">
                    <legend class="sr-only">Тип заявки</legend>
                    <x-radio name="sg_type" value="adopt" checked>Забрать домой</x-radio>
                    <x-radio name="sg_type" value="guardianship">Взять под опеку</x-radio>
                    <x-radio name="sg_type" value="none" disabled>Недоступно</x-radio>
                </fieldset>
                <div class="grid content-start gap-2">
                    <x-toggle name="sg_anonymous" checked>Пожертвовать анонимно</x-toggle>
                    <x-toggle name="sg_walk_only">Только готовые к выгулу</x-toggle>
                </div>
            </div>
        </section>

        {{-- ========== Фильтры и суммы ========== --}}
        <section id="filters" class="{{ $sg }}" aria-labelledby="filters-h">
            <h2 id="filters-h" class="type-h2">Фильтры и суммы</h2>
            <p class="mt-3 max-w-prose text-secondary">Чипы показывают число найденных питомцев. Суммы пожертвования — крупные кнопки-пресеты.</p>
            <div class="mt-8 flex flex-wrap gap-2">
                <x-chip href="#filters" active :count="24">Собаки</x-chip>
                <x-chip href="#filters" :count="17">Кошки</x-chip>
                <x-chip href="#filters" :count="6">Щенки и котята</x-chip>
                <x-chip name="sg_filter[]" value="kids">Ладит с детьми</x-chip>
                <x-chip name="sg_filter[]" value="walk" active>Можно погулять</x-chip>
            </div>
            <div class="mt-8 max-w-2xl">
                <x-amount-picker name="sg_donation" :value="5000" />
            </div>
        </section>

        {{-- ========== Статусы ========== --}}
        <section id="statuses" class="{{ $sg }}" aria-labelledby="statuses-h">
            <h2 id="statuses-h" class="type-h2">Статусы</h2>
            <p class="mt-3 max-w-prose text-secondary">Бейдж похож на бирку: слева пробито отверстие. Статус всегда передаётся текстом, цвет только помогает.</p>
            <dl class="mt-8 grid gap-5">
                @foreach ($statuses as $group => $cases)
                    <div class="grid gap-3 md:grid-cols-[200px_1fr] md:items-center">
                        <dt class="type-label">{{ $group }}</dt>
                        <dd class="flex flex-wrap gap-2">
                            @foreach ($cases as $case)
                                <x-badge :status="$case" />
                            @endforeach
                        </dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-10 grid gap-8 md:grid-cols-3">
                <x-progress :raised="48_000_00" :goal="600_000_00" />
                <x-progress :raised="412_500_00" :goal="600_000_00" />
                <x-progress :raised="250_000_00" :goal="250_000_00" />
            </div>
        </section>

        {{-- ========== Слоты ========== --}}
        <section id="slots" class="{{ $sg }}" aria-labelledby="slots-h">
            <h2 id="slots-h" class="type-h2">Слоты выгула</h2>
            <p class="mt-3 max-w-prose text-secondary">Время крупно, под ним — сколько осталось мест. Заполненный и прошедший слоты отличаются не только цветом: иконкой, текстом, пунктиром и зачёркиванием.</p>

            <fieldset class="mt-8">
                <legend class="sr-only">Пример выбора времени</legend>
                <div class="flex flex-wrap gap-3">
                    <x-walk-slot name="sg_slot" value="1" :start="$base->copy()->addDay()" :end="$base->copy()->addDay()->addHour()" state="available" :spots="3" />
                    <x-walk-slot name="sg_slot" value="2" :start="$base->copy()->addDay()->addHours(2)" :end="$base->copy()->addDay()->addHours(3)" state="few_left" :spots="1" />
                    <x-walk-slot name="sg_slot" value="3" :start="$base->copy()->addDay()->addHours(4)" :end="$base->copy()->addDay()->addHours(5)" state="available" :spots="4" checked />
                    <x-walk-slot name="sg_slot" value="4" :start="$base->copy()->addDay()->addHours(7)" :end="$base->copy()->addDay()->addHours(8)" state="full" :spots="0" />
                    <x-walk-slot name="sg_slot" value="5" :start="$base->copy()->subHours(2)" :end="$base->copy()->subHour()" state="past" :spots="0" />
                </div>
            </fieldset>

            <div class="mt-10 grid gap-6" data-day-scope>
                <x-day-picker :days="$days" name="sg_day" label="День выгула" />
                @foreach ($days as $i => $day)
                    <div data-day-panel="{{ \App\Support\LocalTime::of($day['date'])->format('Y-m-d') }}" class="rounded-lg border border-dashed border-ink-300 p-5 text-sm text-secondary">
                        @if ($day['available'])
                            Слоты на {{ \App\Support\LocalTime::dayLong($day['date']) }} — здесь будет список (этап 10).
                        @else
                            {{ \Illuminate\Support\Str::ucfirst(\App\Support\LocalTime::dayLong($day['date'])) }}: выгула нет.
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ========== Карточки ========== --}}
        <section id="cards" class="{{ $sg }}" aria-labelledby="cards-h">
            <h2 id="cards-h" class="type-h2">Карточки</h2>
            <p class="mt-3 max-w-prose text-secondary">Карточка питомца — главный объект интерфейса: вся карточка — одна ссылка. Тень появляется только при наведении.</p>

            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
                <x-pet-card :pet="$pets[0]" href="#cards" favorite-url="#cards" />
                <x-pet-card :pet="$pets[1]" href="#cards" favorite-url="#cards" favorited />
                <x-pet-card :pet="$pets[2]" href="#cards" favorite-url="#cards" />
                <x-pet-card-skeleton />
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                <x-campaign-card :campaign="$campaigns[0]" href="#cards" />
                <x-campaign-card :campaign="$campaigns[1]" href="#cards" />
                <x-shelter-card :shelter="$domLap" href="#cards" district="Бостандыкский район" :pets-count="38" :campaigns-count="2" walks="сб, вс" class="self-start" />
            </div>

            <h3 class="type-h3 mt-12">Три пути помощи</h3>
            <div class="mt-6 grid gap-4 md:grid-cols-3 md:gap-6">
                <x-help-path path="adopt" title="Забрать домой" href="#cards" action="Смотреть питомцев">Выберите питомца и оставьте заявку. Приют перезвонит и расскажет, как познакомиться.</x-help-path>
                <x-help-path path="donate" title="Пожертвовать" href="#cards" action="Выбрать сбор">Любая сумма от 200 ₸, без регистрации. По каждому сбору приют публикует отчёт.</x-help-path>
                <x-help-path path="walk" title="Погулять с собакой" href="#cards" action="Найти время">Запишитесь на свободное время в приюте рядом. Прогулка занимает около часа.</x-help-path>
            </div>
        </section>

        {{-- ========== Навигация ========== --}}
        <section id="navigation" class="{{ $sg }}" aria-labelledby="navigation-h">
            <h2 id="navigation-h" class="type-h2">Навигация</h2>
            <p class="mt-3 max-w-prose text-secondary">Шапка, подвал и мобильное меню — на этой же странице (уменьшите окно, чтобы увидеть кнопку меню).</p>
            <div class="mt-8 grid grid-cols-1 gap-10 lg:grid-cols-2">
                <div class="grid min-w-0 grid-cols-1 content-start gap-8">
                    <div>
                        <h3 class="type-label mb-3">Хлебные крошки</h3>
                        <x-breadcrumbs :items="['Главная' => '#navigation', 'Питомцы' => '#navigation', 'Бублик' => null]" />
                    </div>
                    <div>
                        <h3 class="type-label mb-3">Пагинация</h3>
                        <x-pagination :paginator="$paginator" />
                    </div>
                </div>
                <div class="grid min-w-0 grid-cols-1 content-start gap-8">
                    <div>
                        <h3 class="type-label mb-3">Вкладки</h3>
                        <x-tabs label="Мои выгулы" :items="[
                            ['label' => 'Предстоящие', 'href' => '#navigation', 'active' => true, 'count' => 2],
                            ['label' => 'Прошедшие', 'href' => '#navigation'],
                            ['label' => 'Отменённые', 'href' => '#navigation'],
                        ]" />
                    </div>
                    <div>
                        <h3 class="type-label mb-3">Аватары</h3>
                        <div class="flex items-center gap-3">
                            <x-avatar name="Айгерим Сейткали" />
                            <x-avatar name="Ерлан Касымов" />
                            <x-avatar name="Дом лап" />
                            <x-avatar name="Айгерим Сейткали" size="lg" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ========== Отклик ========== --}}
        <section id="feedback" class="{{ $sg }}" aria-labelledby="feedback-h">
            <h2 id="feedback-h" class="type-h2">Модальное окно и уведомления</h2>
            <p class="mt-3 max-w-prose text-secondary">Модальное окно — только для подтверждения необратимых действий. Тосты сообщают результат тем же глаголом, что был на кнопке.</p>

            <div class="mt-8 flex flex-wrap gap-3">
                <x-button variant="danger" type="button" data-modal-open="sg-cancel">Отменить запись</x-button>
                <x-button variant="secondary" type="button" onclick="VetMoment.toast({ title: 'Вы записаны на выгул', text: 'Суббота, 10:00. Приют подтвердит запись до пятницы, 18:00.' })">Показать тост</x-button>
            </div>

            <x-modal id="sg-cancel" title="Отменить запись на выгул?">
                Суббота, 8 ноября, 10:00–11:00, Бублик. Приют освободит место для другого волонтёра. Отменить можно не позже чем за 3 часа.
                <x-slot:footer>
                    <x-button variant="secondary" type="button" data-modal-close>Оставить</x-button>
                    <x-button variant="danger" type="button" data-modal-close>Отменить запись</x-button>
                </x-slot:footer>
            </x-modal>

            <div class="mt-8 grid gap-4 lg:grid-cols-2">
                <div class="grid content-start gap-4">
                    <x-toast tone="success" title="Вы записаны на выгул" :autohide="false">Суббота, 10:00. Приют подтвердит запись до пятницы, 18:00.</x-toast>
                    <x-toast tone="danger" title="Платёж не прошёл">Банк отклонил карту. Проверьте данные или выберите другую карту.</x-toast>
                    <x-toast tone="info" title="Заявка отправлена" :autohide="false">Приют «Дом лап» свяжется с вами по телефону в течение двух дней.</x-toast>
                </div>
                <div class="grid content-start gap-4">
                    <x-alert tone="warning" title="Приют на модерации">Можно готовить черновики анкет — опубликовать их получится после проверки.</x-alert>
                    <x-alert tone="danger" title="Проверьте форму"><ul class="list-disc pl-5"><li>Не хватает цифр: нужно 10 после +7</li></ul></x-alert>
                    <x-alert tone="success" title="Изменения сохранены" />
                    <x-alert tone="info">Отменить запись можно не позже чем за 3 часа до начала.</x-alert>
                </div>
            </div>

            <h3 class="type-h3 mt-12">Пустое состояние</h3>
            <x-empty-state title="Вы ещё не записывались на выгул" class="mt-6" :heading-level="4">
                Выберите приют и удобное время. Первая прогулка занимает около часа.
                <x-slot:action><x-button href="#feedback">Найти время</x-button></x-slot:action>
            </x-empty-state>
        </section>

        {{-- ========== Таблица и галерея ========== --}}
        <section id="data" class="{{ $sg }}" aria-labelledby="data-h">
            <h2 id="data-h" class="type-h2">Таблица и галерея</h2>
            <div class="mt-8">
                <x-table caption="Питомцы приюта">
                    <x-slot:head>
                        <tr><x-table.th>Питомец</x-table.th><x-table.th>Статус</x-table.th><x-table.th>Выгул</x-table.th><x-table.th align="right">Действия</x-table.th></tr>
                    </x-slot:head>
                    @foreach ($pets as $pet)
                        <tr>
                            <td><p class="font-semibold">{{ $pet->name }}</p><p class="text-muted">{{ $pet->summary() }}</p></td>
                            <td><x-badge :status="$pet->status" /></td>
                            <td>{{ $pet->isWalkable() ? 'Можно' : 'Нельзя' }}</td>
                            <td class="text-right"><x-button variant="ghost" size="sm" icon="edit" href="#data">Изменить</x-button></td>
                        </tr>
                    @endforeach
                </x-table>
            </div>

            <div class="mt-10 max-w-md">
                <x-gallery alt="Бублик" :images="[
                    asset('images/illustrations/dog-caramel.svg'),
                    asset('images/illustrations/dog-graphite.svg'),
                    asset('images/illustrations/cat-ginger.svg'),
                    asset('images/illustrations/cat-smoke.svg'),
                ]" />
            </div>
        </section>

        {{-- ========== Иконки ========== --}}
        <section id="icons" class="{{ $sg }}" aria-labelledby="icons-h">
            <h2 id="icons-h" class="type-h2">Иконки</h2>
            <p class="mt-3 max-w-prose text-secondary">Сетка 24, линия 2 px. <code>&lt;x-icon name="paw" size="sm|md|lg" /&gt;</code>; без <code>label</code> иконка скрыта от скринридеров.</p>
            <ul class="mt-8 grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-8">
                @foreach ($icons as $icon)
                    <li class="grid justify-items-center gap-2 rounded-md border border-subtle p-4">
                        <x-icon :name="$icon" size="lg" />
                        <span class="type-caption text-muted">{{ $icon }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <x-sticky-bar>
        <x-button icon="leash" href="#slots">Погулять</x-button>
        <x-button variant="secondary" href="#cards">Хочу забрать</x-button>
    </x-sticky-bar>
    <div class="h-20 lg:hidden"></div>
</x-layouts.app>
