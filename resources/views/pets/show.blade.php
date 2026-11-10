@use('App\Enums\ApplicationStatus')
@use('App\Enums\ApplicationType')
@use('App\Enums\PetStatus')
@use('App\Enums\WalkSlotState')
@use('App\Support\LocalTime')
@use('App\Support\Phone')
@use('App\Support\Plural')
@use('Illuminate\Support\Facades\Route')
{{-- Карточка питомца (макет 03): галерея, кто он, три пути помощи, приют, характер и здоровье, история,
     ближайшие прогулки, похожие питомцы. На телефоне главные действия — в липкой панели снизу. --}}
@php
    $shelter = $pet->shelter;
    $photos = $pet->photos->values()->map(fn ($photo, $i) => [
        'src' => $photo->url(),
        'thumb' => $photo->thumbUrl(),
        'alt' => $pet->photoAlt($i + 1),
    ])->all();
    $isAdopted = $pet->status === PetStatus::Adopted;
    $isPublic = in_array($pet->status, PetStatus::public(), true) && ! $pet->is_hidden && $shelter->isApproved();
    $applyUrl = fn (ApplicationType $type) => route('pets.apply', [$pet, 'type' => $type->value]);
    $walkUrl = route('walks.index', ['pet' => $pet->slug]);
    $shelterUrl = Route::has('shelters.show') ? route('shelters.show', $shelter) : url('/shelters/'.$shelter->slug);
    $helpUrl = $campaign
        ? (Route::has('campaigns.show') ? route('campaigns.show', $campaign) : url('/campaigns/'.$campaign->slug))
        : (Route::has('shelters.donate') ? route('shelters.donate', $shelter) : url('/shelters/'.$shelter->slug.'/donate'));
    $slotUrl = fn ($slot) => route('walks.slots.show', [$slot, 'pet' => $pet->slug]);
    $adoption = $applications->get(ApplicationType::Adoption->value);
    $guardianship = $applications->get(ApplicationType::Guardianship->value);
    $showApply = $canApply && ! $isAdopted;
    $description = Str::limit($pet->fullSummary().'. '.$pet->character, 155);
@endphp
<x-layouts.app :title="$pet->name.' — '.mb_strtolower($pet->species->label()).' из приюта «'.$shelter->name.'»'" :description="$description">
    <x-slot:head>
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $pet->name }} — {{ $pet->status->label() }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ route('pets.show', $pet) }}">
        @if ($photos)
            <meta property="og:image" content="{{ $photos[0]['src'] }}">
        @endif
        @unless ($isPublic)
            <meta name="robots" content="noindex">
        @endunless
    </x-slot:head>

    <div @class(['page-container pt-6 lg:pt-8 lg:pb-24', 'pb-28' => $showApply, 'pb-16' => ! $showApply])>
        <x-breadcrumbs :items="['Главная' => route('home'), 'Питомцы' => route('pets.index'), $pet->name => null]" />

        @unless ($isPublic)
            <x-alert tone="warning" title="Анкета не видна на сайте" class="mt-6">
                {{ collect([
                    'Статус «'.$pet->status->label().'»',
                    $pet->is_hidden ? 'анкета скрыта модератором' : null,
                    $shelter->isApproved() ? null : 'приют ещё не прошёл проверку',
                ])->filter()->implode(', ') }}.
                Эту страницу видят только сотрудники приюта и администраторы.
            </x-alert>
        @endunless

        <div class="mt-6 grid gap-8 lg:mt-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,472px)] lg:gap-12">
            {{-- Галерея --}}
            <div class="min-w-0">
                @if ($photos)
                    <x-gallery :images="$photos" />
                @else
                    <div class="relative aspect-square overflow-hidden rounded-lg bg-sky-100 md:aspect-[4/3]">
                        <img src="{{ asset('images/illustrations/'.($pet->species === \App\Enums\Species::Cat ? 'cat-smoke' : 'dog-caramel').'.svg') }}" alt=""
                             class="absolute inset-x-0 bottom-0 mx-auto h-[86%] w-auto">
                    </div>
                @endif
            </div>

            {{-- Кто он и как помочь --}}
            <div class="grid min-w-0 content-start gap-6">
                <div>
                    <div class="flex items-start justify-between gap-4">
                        <x-badge :status="$pet->status" />
                        <div class="flex gap-2">
                            <x-button variant="secondary" size="sm" icon="share" icon-only label="Поделиться анкетой" type="button"
                                      data-share="{{ route('pets.show', $pet) }}" data-share-title="{{ $pet->name }} ищет дом" />
                            @if ($isPublic)
                                <form method="POST" action="{{ route('pets.favorite', $pet) }}">
                                    @csrf
                                    <x-button variant="secondary" size="sm" :icon="$favorited ? 'heart-filled' : 'heart'" icon-only
                                              :label="$favorited ? 'Убрать из избранного' : 'Добавить в избранное'"
                                              aria-pressed="{{ $favorited ? 'true' : 'false' }}" @class(['text-danger-solid' => $favorited]) />
                                </form>
                            @endif
                        </div>
                    </div>
                    <h1 class="type-h1 mt-3 break-words">{{ $pet->name }}</h1>
                    <p class="type-lead mt-2 text-secondary">{{ $pet->fullSummary() }}</p>
                    @if ($pet->breed || $pet->color)
                        <p class="mt-1 text-sm text-muted">{{ collect([$pet->breed, $pet->color])->filter()->implode(' · ') }}</p>
                    @endif
                    @if ($traits = $pet->traitLabels())
                        <ul class="mt-4 flex flex-wrap gap-2" aria-label="Характер коротко">
                            @foreach ($traits as $trait)
                                <li class="rounded-pill bg-ink-100 px-3 py-1.5 text-sm font-medium text-secondary">{{ mb_strtolower($trait) }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <x-card padding="lg" class="grid gap-4">
                    @if ($canManage)
                        <x-button variant="secondary" icon="edit" :href="route('shelter.pets.edit', $pet)" block>Редактировать анкету</x-button>
                    @endif

                    @if ($isAdopted)
                        <x-alert tone="success" title="{{ $pet->name }} нашёл дом">
                            Спасибо всем, кто помогал. В каталоге ждут другие питомцы.
                        </x-alert>
                        <x-button :href="route('pets.index')" icon="paw" block>Смотреть питомцев</x-button>
                    @elseif ($showApply)
                        @if ($pet->status === PetStatus::Reserved)
                            <p class="flex items-start gap-2 text-sm text-secondary">
                                <x-icon name="info" size="sm" class="mt-0.5 text-info-fg" />
                                {{ $pet->name }} уже забронирован. Можно оставить заявку в резерв — приют напишет, если планы изменятся.
                            </p>
                        @endif

                        @foreach (array_filter([$adoption, $guardianship]) as $application)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-canvas p-4">
                                <div class="grid gap-1 text-sm">
                                    <p class="font-semibold">Ваша заявка «{{ $application->type->label() }}»</p>
                                    <x-badge :status="$application->status" class="justify-self-start" />
                                </div>
                                @can('withdraw', $application)
                                    <form method="POST" action="{{ route('applications.withdraw', $application) }}">
                                        @csrf
                                        <x-button variant="ghost" size="sm">Отозвать заявку</x-button>
                                    </form>
                                @endcan
                            </div>
                        @endforeach

                        @unless ($adoption)
                            <x-button :href="$applyUrl(ApplicationType::Adoption)" icon="home" block>Хочу забрать</x-button>
                        @endunless
                        @if (($pet->guardianship_available && ! $guardianship) || $pet->isWalkable())
                            <div class="grid gap-3 sm:grid-cols-2">
                                @if ($pet->guardianship_available && ! $guardianship)
                                    <x-button variant="secondary" :href="$applyUrl(ApplicationType::Guardianship)" @class(['sm:col-span-2' => ! $pet->isWalkable()])>Взять под опеку</x-button>
                                @endif
                                @if ($pet->isWalkable())
                                    <x-button variant="secondary" icon="leash" :href="$walkUrl" @class(['sm:col-span-2' => ! $pet->guardianship_available || $guardianship])>Погулять</x-button>
                                @endif
                            </div>
                        @endif
                    @endif

                    @unless ($isAdopted)
                        <div class="grid gap-3 border-t border-subtle pt-4">
                            @if ($campaign)
                                <p class="text-sm text-secondary">Сбор «{{ $campaign->title }}»</p>
                                <x-progress :raised="$campaign->raised_amount" :goal="$campaign->goal_amount" />
                                <div><x-button variant="donate" size="sm" icon="heart" :href="$helpUrl">Помочь сбору</x-button></div>
                            @else
                                <p class="text-sm text-secondary">
                                    Не готовы забрать? Помогите приюту, где живёт {{ $pet->name }}: деньги пойдут на корм и лечение.
                                </p>
                                <div><x-button variant="donate" size="sm" icon="heart" :href="$helpUrl">Помочь приюту</x-button></div>
                            @endif
                        </div>
                    @endunless
                </x-card>

                <section aria-labelledby="pet-shelter" class="grid gap-3 rounded-lg bg-canvas p-5">
                    <div class="flex items-start gap-3">
                        <span class="grid size-11 shrink-0 place-items-center rounded-pill bg-meadow-100 text-meadow-700"><x-icon name="home" /></span>
                        <div class="min-w-0">
                            <h2 id="pet-shelter" class="font-semibold">{{ $shelter->name }}</h2>
                            <p class="text-sm text-secondary">{{ str_contains($shelter->address, $shelter->city) ? $shelter->address : $shelter->city.', '.$shelter->address }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 text-sm">
                        <a href="{{ $shelterUrl }}" class="link inline-flex items-center gap-1 font-semibold">Страница приюта<x-icon name="chevron-right" size="sm" /></a>
                        @if ($shelter->phone)
                            <a href="tel:{{ $shelter->phone }}" class="inline-flex items-center gap-2 text-secondary hover:text-primary">
                                <x-icon name="phone" size="sm" />{{ Phone::format($shelter->phone) }}
                            </a>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        {{-- Характер, здоровье, история --}}
        <div class="mt-12 grid gap-10 lg:mt-16 lg:grid-cols-[minmax(0,1fr)_minmax(0,472px)] lg:gap-12">
            <div class="grid min-w-0 content-start gap-10 lg:gap-14">
                <div class="grid gap-10 md:grid-cols-2">
                    <section aria-labelledby="pet-character">
                        <h2 id="pet-character" class="type-h3">Характер</h2>
                        <p class="mt-3 text-secondary">{{ $pet->character }}</p>
                        <ul class="mt-4 grid gap-2.5">
                            @foreach ($pet->characterList() as $item)
                                <li class="flex items-center gap-3 text-sm">
                                    <span @class(['grid size-6 shrink-0 place-items-center rounded-pill', 'bg-meadow-100 text-meadow-700' => $item['value'], 'bg-ink-100 text-ink-500' => ! $item['value']])>
                                        <x-icon :name="$item['value'] ? 'check' : 'x'" size="sm" class="size-3.5" />
                                    </span>
                                    <span><span class="sr-only">{{ $item['value'] ? 'Да:' : 'Нет:' }}</span> {{ $item['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>

                    <section aria-labelledby="pet-health">
                        <h2 id="pet-health" class="type-h3">Здоровье</h2>
                        <ul class="mt-4 grid gap-2.5">
                            @foreach ($pet->healthList() as $item)
                                <li @class(['flex items-center gap-3 text-sm', 'text-muted' => ! $item['value']])>
                                    <x-icon :name="$item['icon']" size="sm" @class(['text-sky-700' => $item['value']]) />{{ $item['label'] }}
                                </li>
                            @endforeach
                            @if ($pet->health_notes)
                                <li class="flex items-start gap-3 text-sm">
                                    <x-icon name="info" size="sm" class="mt-0.5 text-sky-700" /><span>{{ $pet->health_notes }}</span>
                                </li>
                            @endif
                        </ul>
                    </section>
                </div>

                @if ($pet->story)
                    <section aria-labelledby="pet-story" class="max-w-prose">
                        <h2 id="pet-story" class="type-h3">История</h2>
                        <div class="mt-3 grid gap-3 text-secondary">
                            @foreach (preg_split('/\R{2,}/', trim($pet->story)) as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                        @if ($pet->arrived_at)
                            <p class="mt-3 text-sm text-muted">В приюте с {{ LocalTime::date($pet->arrived_at) }}</p>
                        @endif
                    </section>
                @endif
            </div>
        </div>

        @if ($slots->isNotEmpty())
            <section aria-labelledby="pet-walks" class="mt-12 lg:mt-16">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <h2 id="pet-walks" class="type-h3">Ближайшие прогулки</h2>
                    <a href="{{ $walkUrl }}" class="link inline-flex items-center gap-1 text-sm font-semibold">Все слоты приюта<x-icon name="chevron-right" size="sm" /></a>
                </div>
                <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,472px)] lg:gap-12">
                    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ($slots as $slot)
                            @php
                                $state = $slot->state();
                                $spots = $slot->spotsLeft();
                            @endphp
                            <li class="min-w-0">
                                <p class="text-sm font-semibold">{{ Str::ucfirst(LocalTime::weekdayShort($slot->starts_at)) }}, {{ LocalTime::date($slot->starts_at) }}</p>
                                <a href="{{ $slotUrl($slot) }}" @class([
                                    'mt-2 grid gap-1 rounded-sm border-control px-3 py-2.5 transition-colors',
                                    'border-meadow-600 hover:bg-meadow-100' => $state === WalkSlotState::Available,
                                    'border-tag-600 hover:bg-tag-100' => $state === WalkSlotState::FewLeft,
                                    'pointer-events-none border-ink-200 bg-ink-100 text-ink-500' => ! $state->isBookable(),
                                ]) @unless ($state->isBookable()) aria-disabled="true" @endunless>
                                    <time datetime="{{ LocalTime::iso($slot->starts_at) }}" class="font-display font-semibold tracking-display tabular-nums">{{ LocalTime::timeRange($slot->starts_at, $slot->ends_at) }}</time>
                                    <span @class(['flex items-center gap-1.5 text-xs font-semibold', 'text-meadow-700' => $state === WalkSlotState::Available, 'text-warning-fg' => $state === WalkSlotState::FewLeft])>
                                        <x-icon :name="match ($state) { WalkSlotState::Available => 'leash', WalkSlotState::FewLeft => 'alert-triangle', default => 'x' }" size="sm" class="size-3.5" />
                                        {{ $state->isBookable() ? 'Осталось '.Plural::format($spots, ['место', 'места', 'мест']) : 'Мест нет' }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <x-alert tone="info" title="Перед первой прогулкой" class="self-start">
                        Возьмите удостоверение личности: приют сверит его с записью. Гулять можно с 18 лет.
                    </x-alert>
                </div>
            </section>
        @endif

        @if ($similar->isNotEmpty())
            <section aria-labelledby="pet-similar" class="mt-12 lg:mt-16">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <h2 id="pet-similar" class="type-h2">Похожие питомцы</h2>
                    <a href="{{ route('pets.index', ['species' => [$pet->species->value]]) }}" class="link inline-flex items-center gap-1 text-sm font-semibold">
                        {{ \App\Services\PetCatalog::SPECIES_LABELS[$pet->species->value] === 'Другие' ? 'Все питомцы' : 'Все '.mb_strtolower(\App\Services\PetCatalog::SPECIES_LABELS[$pet->species->value]) }}<x-icon name="chevron-right" size="sm" />
                    </a>
                </div>
                <ul class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4 lg:gap-8">
                    @foreach ($similar as $item)
                        <li class="flex min-w-0">
                            <x-pet-card :pet="$item" class="w-full" responsive
                                        :favorite-url="route('pets.favorite', $item)" :favorited="in_array($item->id, $favoriteIds, true)" />
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    @if ($showApply && ! $adoption)
        <x-sticky-bar>
            @if ($pet->isWalkable())
                <x-button variant="secondary" icon="leash" :href="$walkUrl">Погулять</x-button>
            @endif
            <x-button icon="home" :href="$applyUrl(ApplicationType::Adoption)">Хочу забрать</x-button>
        </x-sticky-bar>
    @endif
</x-layouts.app>
