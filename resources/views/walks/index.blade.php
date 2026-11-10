@use('App\Enums\WalkSlotState')
@use('App\Support\LocalTime')
@use('App\Support\Plural')
{{-- Запись на выгул, шаги 1–2 (макет 06): приют и собака, календарь недели, выбранное время → «Продолжить».
     Десктоп — 7 колонок дней; телефон — лента дней и слоты выбранного дня, «Продолжить» в липкой панели.
     Всё работает без JS: фильтры — GET-форма с кнопкой, без скрипта на телефоне видна вся неделя. --}}
@php
    $weekUrl = fn (string $week) => route('walks.index', array_filter(['shelter' => $shelter?->slug, 'pet' => $pet?->slug, 'week' => $week]));
    $place = fn ($slot) => $slot->meeting_point ?: trim($shelter->address ?? '');
    $legend = [
        ['leash', 'text-meadow-700', 'Свободно'],
        ['alert-triangle', 'text-warning-fg', 'Мало мест'],
        ['x', 'text-secondary', 'Мест нет'],
        ['clock', 'text-secondary', 'Прошёл или запись закрыта'],
    ];
@endphp
<x-layouts.app title="Запись на выгул" description="Погуляйте с собакой из приюта: выберите приют и свободное время, приют подтвердит запись.">
    <div class="page-container pt-6 pb-32 lg:pt-8 lg:pb-24">
        <x-breadcrumbs :items="['Главная' => route('home'), 'Запись на выгул' => null]" class="max-lg:hidden" />

        <h1 class="type-h1 mt-2 lg:mt-6">Запись на выгул</h1>
        <p class="type-lead mt-3 max-w-[640px] text-secondary max-sm:hidden">
            Выберите приют и свободное время. Прогулка — около часа, приют выдаст поводок и расскажет о собаке.
        </p>

        <x-steps :steps="['Приют', 'Время', 'Подтверждение']" :current="$shelter ? 2 : 1" class="mt-6 lg:mt-10" />

        @if (! $shelter)
            <x-empty-state title="Пока ни один приют не открыл запись" class="mt-8" illustration="dog-caramel">
                Приюты публикуют время прогулок на две недели вперёд. Загляните позже или выберите собаку в каталоге —
                у многих в анкете есть ближайшие прогулки.
                <x-slot:action><x-button variant="secondary" icon="paw" :href="route('pets.index', ['walk' => 1])">Собаки, готовые к прогулке</x-button></x-slot:action>
            </x-empty-state>
        @else
            {{-- Шаг 1: приют и собака --}}
            <form method="GET" action="{{ route('walks.index') }}" id="walk-filter" data-autosubmit
                  class="mt-6 grid gap-5 lg:mt-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,480px)] lg:items-end lg:gap-8">
                <input type="hidden" name="week" value="{{ $week->key() }}">

                <nav aria-label="Приют" class="max-lg:hidden">
                    <ul class="grid grid-cols-3 gap-4">
                        @foreach ($shelters as $item)
                            @php $current = $item->is($shelter); @endphp
                            <li>
                                <a href="{{ route('walks.index', ['shelter' => $item->slug]) }}" @if ($current) aria-current="true" @endif
                                   @class([
                                       'flex h-full items-center gap-3 rounded-md border-control px-4 py-3 transition-colors duration-120',
                                       'border-ink-900 bg-meadow-100' => $current,
                                       'border-ink-200 bg-white hover:border-ink-900' => ! $current,
                                   ])>
                                    <span @class([
                                        'size-5 shrink-0 rounded-pill bg-white',
                                        'border-[6px] border-ink-900' => $current,
                                        'border-control border-strong' => ! $current,
                                    ]) aria-hidden="true"></span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold">{{ $item->name }}</span>
                                        <span class="block text-sm text-secondary">{{ $item->city }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-1">
                    <x-select name="shelter" label="Приют" class="lg:hidden" :value="$shelter->slug"
                              :options="$shelters->mapWithKeys(fn ($s) => [$s->slug => $s->name.', '.$s->city])->all()" />
                    <x-select name="pet" label="Собака (необязательно)" :value="$pet?->slug" placeholder="Любая собака"
                              :options="$dogs->pluck('name', 'slug')->all()"
                              hint="Покажем время, когда гуляет эта собака" />
                </div>

                <div data-autosubmit-fallback>
                    <x-button variant="secondary" type="submit">Показать время</x-button>
                </div>
            </form>

            {{-- Шаг 2: неделя --}}
            <div class="mt-10 flex flex-wrap items-center justify-between gap-4 lg:mt-12">
                <div class="flex items-center gap-4">
                    <h2 class="type-h2 order-2 lg:order-none"><span class="sr-only">Неделя: </span>{{ $week->label() }}</h2>
                    <div class="order-3 flex gap-2 lg:contents">
                        @php
                            $navClass = 'grid size-11 place-items-center rounded-sm border-control transition-colors duration-120';
                        @endphp
                        @if ($canPrev)
                            <a href="{{ $weekUrl($week->previousKey()) }}" class="{{ $navClass }} border-ink-900 bg-white hover:bg-ink-50 lg:order-first" aria-label="Предыдущая неделя">
                                <x-icon name="chevron-left" />
                            </a>
                        @else
                            <span class="{{ $navClass }} border-ink-200 text-ink-300 lg:order-first" aria-hidden="true"><x-icon name="chevron-left" /></span>
                        @endif
                        @if ($canNext)
                            <a href="{{ $weekUrl($week->nextKey()) }}" class="{{ $navClass }} border-ink-900 bg-white hover:bg-ink-50 lg:order-last" aria-label="Следующая неделя">
                                <x-icon name="chevron-right" />
                            </a>
                        @else
                            <span class="{{ $navClass }} border-ink-200 text-ink-300 lg:order-last" aria-hidden="true"><x-icon name="chevron-right" /></span>
                        @endif
                    </div>
                </div>
                <ul class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-secondary max-lg:hidden" aria-label="Обозначения">
                    @foreach ($legend as [$icon, $color, $label])
                        <li class="flex items-center gap-1.5"><x-icon :name="$icon" size="sm" :class="$color" />{{ $label }}</li>
                    @endforeach
                </ul>
            </div>

            @error('slot_id')
                <x-alert tone="danger" class="mt-6">{{ $message }}</x-alert>
            @enderror

            @if (collect($days)->every(fn ($d) => $d['slots']->isEmpty()))
                <x-alert tone="info" class="mt-6" title="На этой неделе прогулок нет">
                    {{ $pet ? 'С '.$pet->name.' в эти дни не гуляют.' : 'Приют ещё не открыл время на эти дни.' }}
                    @if ($canNext)
                        <a href="{{ $weekUrl($week->nextKey()) }}" class="link font-semibold">Посмотреть следующую неделю</a>
                    @endif
                </x-alert>
            @endif

            <form method="GET" action="{{ route('walks.continue') }}" id="walk-slot-form" data-walk-picker class="mt-6">
                @if ($pet)
                    <input type="hidden" name="pet" value="{{ $pet->slug }}">
                @endif

                <div data-day-scope="responsive">
                    <x-day-picker :days="$days" name="day" :selected="$selectedDay" class="lg:hidden" label="День прогулки" />

                    <fieldset class="mt-6 min-w-0 lg:mt-0">
                        <legend class="sr-only">Время прогулки</legend>
                        <div class="grid grid-cols-1 gap-8 lg:grid-cols-7 lg:gap-3">
                            @foreach ($days as $day)
                                <section data-day-panel="{{ $day['key'] }}" aria-labelledby="day-{{ $day['key'] }}"
                                         class="grid min-w-0 content-start gap-3 max-lg:data-day-inactive:hidden">
                                    <h3 id="day-{{ $day['key'] }}">
                                        <span class="type-h4 font-semibold lg:hidden">{{ Str::ucfirst(LocalTime::dayLong($day['date'])) }}</span>
                                        <span @class([
                                            'grid gap-0.5 rounded-sm px-4 py-2.5 max-lg:hidden',
                                            'bg-ink-900 text-white' => $day['today'],
                                            'bg-canvas text-muted' => $day['past'],
                                            'bg-canvas text-primary' => ! $day['today'] && ! $day['past'],
                                        ])>
                                            <span class="flex items-baseline justify-between gap-2 text-xs font-medium">
                                                <span @class(['text-tag-500' => $day['today']])>{{ Str::ucfirst(LocalTime::weekdayShort($day['date'])) }}</span>
                                                @if ($day['today'])
                                                    <span class="text-tag-500">сегодня</span>
                                                @endif
                                            </span>
                                            <span class="font-semibold">{{ LocalTime::date($day['date']) }}</span>
                                        </span>
                                    </h3>

                                    @if ($day['slots']->isEmpty())
                                        <p class="grid min-h-[76px] place-items-center rounded-sm border border-dashed border-ink-200 px-3 text-center text-sm text-muted">Выгула нет</p>
                                    @else
                                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-1">
                                            @foreach ($day['slots'] as $slot)
                                                <x-walk-slot :model="$slot" name="slot_id" class="min-w-0"
                                                    :checked="(int) old('slot_id', $selected) === $slot->id"
                                                    data-summary-title="{{ Str::ucfirst(LocalTime::dayLong($slot->starts_at)) }}, {{ LocalTime::timeRange($slot->starts_at, $slot->ends_at) }}"
                                                    data-summary-text="{{ $shelter->name }}{{ $place($slot) ? '. '.$place($slot) : '' }}. Осталось {{ Plural::format($slot->spotsLeft(), ['место', 'места', 'мест']) }} из {{ $slot->capacity }}." />
                                            @endforeach
                                        </div>
                                    @endif
                                </section>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                {{-- Итог выбора: плашка на десктопе, липкая панель на телефоне --}}
                <div class="mt-10 flex items-center gap-5 rounded-lg bg-path-walk p-5 max-lg:hidden lg:px-7">
                    <span class="grid size-12 shrink-0 place-items-center rounded-pill bg-white text-meadow-700"><x-icon name="calendar" /></span>
                    <div class="min-w-0 flex-1" aria-live="polite">
                        <p class="type-h4 font-semibold" data-walk-summary-title>Выберите время</p>
                        <p class="mt-1 text-sm text-secondary" data-walk-summary-text>Нажмите на свободное время в календаре, затем «Продолжить».</p>
                    </div>
                    <x-button size="lg" icon-right="chevron-right">Продолжить</x-button>
                </div>
            </form>

            <x-sticky-bar class="[&>div]:items-center">
                <div class="min-w-0 flex-[1.4]!">
                    <p class="truncate text-sm font-semibold" data-walk-summary-title>Выберите время</p>
                    <p class="truncate text-xs text-secondary" data-walk-summary-text>{{ $shelter->name }}</p>
                </div>
                <x-button form="walk-slot-form">Продолжить</x-button>
            </x-sticky-bar>
        @endif
    </div>
</x-layouts.app>
