@use('App\Support\LocalTime')
@use('App\Support\Phone')
@use('App\Support\Plural')
{{-- Страница приюта (макет 07): шапка с контактами и действиями, о приюте и правила выгула, питомцы, сборы и отчёты, ближайшие выгулы. --}}
@php
    $logo = $shelter->logo_path ? Storage::disk('public')->url($shelter->logo_path) : null;
    $rules = collect(preg_split('/\r\n|\r|\n/', (string) $shelter->walk_rules))->map(fn ($l) => trim($l, " \t-—•"))->filter()->values();
    $paragraphs = collect(preg_split('/\R{2,}/', (string) $shelter->description))->map(fn ($p) => trim($p))->filter();
    $visible = $shelter->isApproved();
@endphp
<x-layouts.app :title="$shelter->name" :description="Str::limit(strip_tags((string) $shelter->description), 150)">
    <x-slot:head>
        @unless ($visible)<meta name="robots" content="noindex">@endunless
    </x-slot:head>

    <div class="page-container pt-6 pb-16 lg:pt-8 lg:pb-24">
        <x-breadcrumbs :items="['Главная' => route('home'), 'Приюты' => route('shelters.index'), $shelter->name => null]" />

        @unless ($visible)
            <x-shelter-moderation :shelter="$shelter" class="mt-6" />
        @endunless

        <header class="mt-6 rounded-lg bg-path-walk p-6 md:p-10">
            <div class="flex items-center gap-5">
                <span class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-pill bg-white text-meadow-700 md:size-24">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="Логотип приюта «{{ $shelter->name }}»" class="size-full object-cover">
                    @else
                        <x-icon name="home" size="lg" />
                    @endif
                </span>
                <div class="min-w-0">
                    <h1 class="type-h1 break-words">{{ $shelter->name }}</h1>
                    <p class="mt-2"><x-badge :status="$shelter->status" :icon="$visible ? 'shield-check' : null" /></p>
                </div>
            </div>
            <ul class="mt-6 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                <li class="flex items-center gap-2"><x-icon name="map-pin" size="sm" />{{ Str::startsWith($shelter->address, $shelter->city) ? $shelter->address : $shelter->city.', '.$shelter->address }}</li>
                <li class="flex items-center gap-2"><x-icon name="phone" size="sm" /><a href="tel:{{ $shelter->phone }}" class="hover:underline">{{ Phone::format($shelter->phone) }}</a></li>
                <li class="flex items-center gap-2"><x-icon name="mail" size="sm" /><a href="mailto:{{ $shelter->email }}" class="hover:underline">{{ $shelter->email }}</a></li>
            </ul>
            @if ($visible)
                <div class="mt-6 flex flex-wrap gap-3">
                    <x-button variant="donate" icon="heart" :href="route('shelters.donate', $shelter)">Помочь приюту</x-button>
                    <x-button variant="secondary" icon="leash" :href="route('walks.index', ['shelter' => $shelter->slug])">Записаться на выгул</x-button>
                </div>
            @endif
        </header>

        <div class="mt-10 grid gap-10 lg:grid-cols-[1fr_22rem] lg:gap-14">
            <section aria-labelledby="about-heading" class="min-w-0">
                <h2 id="about-heading" class="type-h3">О приюте</h2>
                <div class="mt-4 grid max-w-[65ch] gap-4 text-lg leading-relaxed">
                    @foreach ($paragraphs as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
                @if ($shelter->website)
                    <p class="mt-6"><a href="{{ $shelter->website }}" class="link" rel="noopener nofollow" target="_blank">{{ preg_replace('#^https?://#', '', rtrim($shelter->website, '/')) }}</a></p>
                @endif
            </section>

            @if ($rules->isNotEmpty())
                <aside aria-labelledby="rules-heading" class="self-start rounded-lg bg-canvas p-6">
                    <h2 id="rules-heading" class="flex items-center gap-2 font-semibold"><x-icon name="leash" size="sm" />Правила выгула</h2>
                    <ul class="mt-4 grid gap-3 text-sm">
                        @foreach ($rules as $rule)
                            <li class="flex items-start gap-3"><x-icon name="check-circle" size="sm" class="mt-0.5 shrink-0 text-success-fg" />{{ $rule }}</li>
                        @endforeach
                    </ul>
                </aside>
            @endif
        </div>

        @if ($visible)
            <section class="mt-16" aria-labelledby="pets-heading">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 id="pets-heading" class="type-h2">Питомцы приюта</h2>
                    @if ($petsCount > 0)
                        <a href="{{ route('pets.index', ['shelter' => $shelter->slug]) }}" class="link inline-flex items-center gap-1">Все {{ $petsCount }}<x-icon name="chevron-right" size="sm" /></a>
                    @endif
                </div>
                @if ($pets->isEmpty())
                    <p class="mt-4 text-secondary">Анкеты приюта скоро появятся на сайте.</p>
                @else
                    <div class="mt-8 grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4 lg:gap-6">
                        @foreach ($pets as $pet)
                            <x-pet-card :pet="$pet" class="w-full" responsive heading-level="3" />
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($campaigns->isNotEmpty() || $slots->isNotEmpty())
                <div class="mt-16 grid gap-10 lg:grid-cols-[1fr_16rem] lg:gap-14">
                    <section aria-labelledby="campaigns-heading" class="min-w-0">
                        <div class="flex items-baseline justify-between gap-4">
                            <h2 id="campaigns-heading" class="type-h2">Сборы</h2>
                            <a href="{{ route('campaigns.index', ['shelter' => $shelter->slug]) }}" class="link inline-flex items-center gap-1">Все сборы приюта<x-icon name="chevron-right" size="sm" /></a>
                        </div>
                        <div class="mt-8 grid gap-4 md:grid-cols-2 lg:gap-6">
                            @forelse ($campaigns as $campaign)
                                <x-campaign-card :campaign="$campaign" class="w-full" />
                            @empty
                                <p class="text-secondary">Сейчас приют ничего не собирает. Можно помочь в общий фонд.</p>
                            @endforelse
                        </div>
                    </section>

                    <section aria-labelledby="slots-heading">
                        <h2 id="slots-heading" class="type-h4">Ближайшие выгулы</h2>
                        <ul class="mt-4 grid gap-3">
                            @forelse ($slots as $slot)
                                <li>
                                    <p class="mb-1 text-xs font-semibold text-secondary">{{ Str::ucfirst(LocalTime::weekdayShort($slot->starts_at)) }}, {{ LocalTime::date($slot->starts_at) }}</p>
                                    <x-slot-tile :model="$slot" />
                                </li>
                            @empty
                                <li class="text-sm text-secondary">Свободного времени пока нет — загляните позже.</li>
                            @endforelse
                        </ul>
                        @if ($slots->isNotEmpty())
                            <a href="{{ route('walks.index', ['shelter' => $shelter->slug]) }}" class="link mt-4 inline-flex items-center gap-1 text-sm">Все слоты<x-icon name="chevron-right" size="sm" /></a>
                        @endif
                    </section>
                </div>
            @endif

            @if ($reports->isNotEmpty())
                <section class="mt-16" aria-labelledby="reports-heading">
                    <h2 id="reports-heading" class="type-h2">Отчёты о расходах</h2>
                    <div class="mt-8 grid max-w-3xl gap-8">
                        @foreach ($reports as $report)
                            <div>
                                <a href="{{ route('campaigns.show', $report->campaign) }}" class="link mb-2 inline-block text-sm">Сбор «{{ $report->campaign->title }}»</a>
                                <x-campaign-report :report="$report" heading-level="3" />
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
</x-layouts.app>
