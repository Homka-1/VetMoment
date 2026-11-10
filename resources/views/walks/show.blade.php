@use('App\Support\LocalTime')
@use('App\Support\Phone')
@use('App\Support\Plural')
{{-- Запись на выгул, шаг 3 «Подтверждение» (макет 06-confirm): выбор собаки (необязательно), комментарий,
     18+ и правила приюта; справа — сводка прогулки, правила и что будет дальше. --}}
@php
    $user = auth()->user();
    $bookable = $state->isBookable() && $canBook;
    $rules = collect(preg_split('/\r\n|\r|\n/', (string) $shelter->walk_rules))->map(fn ($l) => trim($l, " \t-—•"))->filter()->values();
    // Срок отмены добавляем сами, если приют не написал о нём в правилах.
    if (! $rules->contains(fn ($rule) => str_contains(mb_strtolower($rule), 'отмен'))) {
        $rules->push('Отменить запись на сайте можно не позже чем за '.$cancelHours.' до начала.');
    }
    $place = $slot->meeting_point ?: trim($shelter->city.', '.$shelter->address, ', ');
    $limit = (int) config('walks.max_active_bookings', 3);
    $chosen = (string) old('pet_id', $selectedPetId ?? '');
    $petsLine = $slot->pets->isEmpty()
        ? 'Собаку подберёт приют на месте — с учётом вашего опыта.'
        : 'Можно выбрать одну собаку или оставить выбор приюту. В этом слоте '.Plural::format($slot->pets->count(), ['собака', 'собаки', 'собак']).'.';
    $blocked = match (true) {
        $user->isAdmin() => ['Администратор не записывается на выгулы', 'Записи на выгул доступны волонтёрам и сотрудникам других приютов.'],
        $user->worksAt($slot->shelter_id) => ['Это прогулка вашего приюта', 'Записи волонтёров на неё — в кабинете приюта.'],
        ! $state->isBookable() => [
            match ($state) {
                \App\Enums\WalkSlotState::Full => 'Мест больше нет',
                \App\Enums\WalkSlotState::Cancelled => 'Приют отменил эту прогулку',
                \App\Enums\WalkSlotState::Closed => 'Запись на это время закрыта',
                default => 'Эта прогулка уже прошла',
            },
            'Выберите другое время — в календаре видно, где остались места.',
        ],
        default => null,
    };
@endphp
<x-layouts.app title="Подтверждение записи на выгул">
    <x-slot:head><meta name="robots" content="noindex"></x-slot:head>

    <div class="page-container pt-6 pb-16 lg:pt-8 lg:pb-24">
        <x-breadcrumbs :items="['Запись на выгул' => $backUrl, 'Подтверждение' => null]" />
        <x-steps :steps="['Приют', 'Время', 'Подтверждение']" :current="3" class="mt-6 lg:mt-8" />

        <div class="mt-8 grid gap-10 lg:mt-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,472px)] lg:gap-16">
            {{-- Сводка прогулки — на телефоне первой --}}
            <aside class="grid content-start gap-8 lg:col-start-2 lg:row-start-1" aria-label="Ваша прогулка">
                <div class="rounded-lg bg-canvas p-6 md:p-7">
                    <p class="text-sm font-semibold text-secondary">Ваша прогулка</p>
                    <p class="type-h2 mt-3">
                        <time datetime="{{ LocalTime::iso($slot->starts_at) }}">
                            {{ Str::ucfirst(LocalTime::weekdayShort($slot->starts_at)) }}, {{ LocalTime::date($slot->starts_at) }}<br>
                            {{ LocalTime::timeRange($slot->starts_at, $slot->ends_at) }}
                        </time>
                    </p>
                    <ul class="mt-5 grid gap-3 text-secondary">
                        <li class="flex items-start gap-3"><x-icon name="home" size="sm" class="mt-1 text-ink-500" />{{ $shelter->name }}, {{ $shelter->city }}</li>
                        @if ($place)
                            <li class="flex items-start gap-3"><x-icon name="map-pin" size="sm" class="mt-1 text-ink-500" />{{ $place }}</li>
                        @endif
                        <li class="flex items-start gap-3"><x-icon name="users" size="sm" class="mt-1 text-ink-500" />
                            {{ $slot->spotsLeft() > 0 ? 'Осталось '.Plural::format($slot->spotsLeft(), ['место', 'места', 'мест']).' из '.$slot->capacity : 'Мест нет' }}
                        </li>
                    </ul>
                </div>

                <section aria-labelledby="rules-title">
                    <h2 id="rules-title" class="type-h4 font-semibold">Правила приюта</h2>
                    <ul class="mt-3 grid list-disc gap-2 pl-5 text-secondary marker:text-ink-400">
                        @foreach ($rules as $rule)
                            <li>{{ $rule }}</li>
                        @endforeach
                    </ul>
                </section>

                @if ($bookable)
                    <x-alert tone="warning" title="Запись ждёт подтверждения">
                        Приют посмотрит запись и подтвердит её — статус появится в разделе «Мои выгулы».
                        @if ($shelter->phone)
                            Если опаздываете, позвоните в приют: {{ Phone::format($shelter->phone) }}.
                        @endif
                    </x-alert>
                @endif
            </aside>

            <div class="min-w-0 lg:col-start-1 lg:row-start-1">
                @if ($blocked)
                    <h1 class="type-h2">{{ $blocked[0] }}</h1>
                    <p class="mt-3 text-secondary">{{ $blocked[1] }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <x-button icon="calendar" :href="$backUrl">Выбрать другое время</x-button>
                    </div>
                @else
                    <h1 class="type-h2">С кем погуляете</h1>
                    <p class="mt-2 text-secondary">{{ $petsLine }}</p>

                    @if ($manyNoShows)
                        <x-alert tone="warning" title="Вы пропустили несколько прогулок" class="mt-6">
                            За последние {{ Plural::format((int) config('walks.no_show_window_days', 90), ['день', 'дня', 'дней']) }} вы
                            не пришли на {{ Plural::format($noShowCount, ['прогулку', 'прогулки', 'прогулок']) }}.
                            Приют увидит это в записи. Если планы меняются — отменяйте запись заранее, собаки ждут.
                        </x-alert>
                    @endif

                    @if ($activeCount >= $limit)
                        <x-alert tone="danger" title="У вас уже {{ Plural::format($limit, ['запись', 'записи', 'записей']) }}" class="mt-6">
                            Это максимум предстоящих прогулок. Сходите на одну из них или отмените запись.
                            <x-slot:action><x-button variant="secondary" size="sm" :href="route('cabinet.walks')">Мои выгулы</x-button></x-slot:action>
                        </x-alert>
                    @endif

                    @error('slot_id')
                        <x-alert tone="danger" class="mt-6">
                            {{ $message }}
                            <x-slot:action><x-button variant="secondary" size="sm" :href="$backUrl">Изменить время</x-button></x-slot:action>
                        </x-alert>
                    @enderror

                    <form method="POST" action="{{ route('walks.book', $slot) }}" class="mt-6 grid gap-8" novalidate>
                        @csrf
                        @if ($slot->pets->isNotEmpty())
                            <fieldset class="min-w-0">
                                <legend class="sr-only">Собака</legend>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($slot->pets as $dog)
                                        @php
                                            $busy = in_array($dog->id, $taken, true);
                                            $traits = array_slice(array_map('mb_strtolower', array_values($dog->traitLabels())), 0, 2);
                                        @endphp
                                        <label @class([
                                            'flex items-center gap-4 rounded-md border-control p-3 pr-4 transition-colors duration-120',
                                            'has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus',
                                            'cursor-pointer border-ink-200 bg-white hover:border-ink-900 has-checked:border-ink-900 has-checked:bg-meadow-100' => ! $busy,
                                            'cursor-not-allowed border-ink-200 bg-white text-ink-400' => $busy,
                                        ])>
                                            <input type="radio" name="pet_id" value="{{ $dog->id }}" @checked($chosen === (string) $dog->id) @disabled($busy)
                                                   class="size-5 shrink-0 cursor-pointer appearance-none rounded-pill border-control border-strong bg-white checked:border-[6px] checked:border-ink-900 disabled:cursor-not-allowed disabled:border-ink-300 disabled:bg-ink-100">
                                            <x-pet-thumb :pet="$dog" size="md" @class(['opacity-50' => $busy]) />
                                            <span class="min-w-0">
                                                <span class="block font-semibold">{{ $dog->name }}</span>
                                                <span class="block text-sm {{ $busy ? '' : 'text-secondary' }}">
                                                    {{ $busy ? 'Уже выбрали другие волонтёры' : ($traits ? implode(', ', $traits) : $dog->summary()) }}
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                    <label class="flex cursor-pointer items-center gap-4 rounded-md border-control border-ink-200 bg-white p-3 pr-4 transition-colors duration-120 hover:border-ink-900 has-checked:border-ink-900 has-checked:bg-meadow-100 has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus">
                                        <input type="radio" name="pet_id" value="" @checked($chosen === '')
                                               class="size-5 shrink-0 cursor-pointer appearance-none rounded-pill border-control border-strong bg-white checked:border-[6px] checked:border-ink-900">
                                        <x-pet-thumb size="md" />
                                        <span class="min-w-0">
                                            <span class="block font-semibold">Любая собака</span>
                                            <span class="block text-sm text-secondary">приют подберёт сам</span>
                                        </span>
                                    </label>
                                </div>
                                @error('pet_id')
                                    <p class="mt-2 flex items-center gap-1.5 text-sm font-medium text-danger-fg" role="alert"><x-icon name="alert-circle" size="sm" />{{ $message }}</p>
                                @enderror
                            </fieldset>
                        @endif

                        <x-textarea name="comment" label="Комментарий для приюта" hint="Необязательно. Например, есть ли опыт с крупными собаками." :maxlength="500" rows="3" />

                        <div class="grid gap-1">
                            <x-checkbox name="adult">Мне есть 18 лет</x-checkbox>
                            <x-checkbox name="rules">С правилами выгула приюта ознакомлен(а)</x-checkbox>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                            <x-button size="lg" icon="leash">Записаться на выгул</x-button>
                            <a href="{{ $backUrl }}" class="font-semibold text-primary underline-offset-3 hover:underline">Изменить время</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
