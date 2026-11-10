{{-- Сборы (макет 04): переключатель «Идут сейчас / Завершённые», фильтр по приюту и «Только на питомца»,
     сетка карточек; в конце списка идущих — блок «Помочь приюту без сбора» (в общий фонд). --}}
@php
    $tabUrl = fn (string $key) => route('campaigns.index', array_filter([
        'tab' => $key === 'active' ? null : $key,
        'shelter' => $shelter?->slug,
        'pet' => $petOnly ? 1 : null,
    ]));
    $shelterOptions = $shelters->mapWithKeys(fn ($s) => [$s->slug => $s->name.', '.$s->city])->all();
    $showFund = $tab === 'active' && $campaigns->onFirstPage() && $fundShelters->isNotEmpty();
    $filtered = $shelter || $petOnly;
@endphp
<x-layouts.app title="Сборы" description="Сборы приютов Казахстана на операции, корм, вакцины и ремонт вольеров. Пожертвовать можно картой без регистрации.">
    <div class="page-container pt-6 pb-16 lg:pt-8 lg:pb-24">
        <x-breadcrumbs :items="['Главная' => route('home'), 'Сборы' => null]" />

        <h1 class="type-h1 mt-6">Сборы</h1>
        <p class="type-lead mt-4 max-w-2xl text-secondary">
            Приюты собирают на конкретные нужды: операцию, корм, ремонт вольеров. Когда сбор закрыт, приют публикует отчёт с чеками.
        </p>

        <div class="mt-8 flex flex-col gap-4 lg:mt-12 lg:flex-row lg:items-center lg:justify-between">
            <nav aria-label="Какие сборы показать">
                <ul class="inline-flex rounded-md bg-ink-100 p-1">
                    @foreach (['active' => 'Идут сейчас', 'finished' => 'Завершённые'] as $key => $label)
                        <li>
                            <a href="{{ $tabUrl($key) }}" @if ($tab === $key) aria-current="page" @endif
                               @class([
                                   'inline-flex h-control items-center rounded-sm px-4 text-sm font-semibold transition-colors duration-120',
                                   'bg-white text-primary shadow-sm' => $tab === $key,
                                   'text-secondary hover:text-primary' => $tab !== $key,
                               ])>{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <form method="GET" action="{{ route('campaigns.index') }}" data-autosubmit class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-8" aria-label="Фильтры сборов">
                @if ($tab !== 'active')
                    <input type="hidden" name="tab" value="{{ $tab }}">
                @endif
                <x-select name="shelter" :options="$shelterOptions" :value="$shelter?->slug" placeholder="Все приюты" aria-label="Приют" class="sm:w-[260px]" />
                <x-toggle name="pet" :checked="$petOnly">Только на питомца</x-toggle>
                <x-button size="sm" variant="secondary" data-autosubmit-fallback>Показать</x-button>
            </form>
        </div>

        @if ($campaigns->isEmpty() && ! $showFund)
            <x-empty-state :title="$filtered ? 'Таких сборов нет' : ($tab === 'active' ? 'Сейчас сборов нет' : 'Завершённых сборов пока нет')"
                           illustration="cat-smoke" tone="tag" class="mt-8">
                @if ($filtered)
                    Уберите фильтры или посмотрите сборы других приютов.
                @else
                    Можно помочь приюту напрямую — деньги уйдут в общий фонд на корм и лечение.
                @endif
                <x-slot:action>
                    @if ($filtered)
                        <x-button variant="secondary" :href="route('campaigns.index', $tab === 'active' ? [] : ['tab' => $tab])">Показать все сборы</x-button>
                    @else
                        <x-button variant="secondary" :href="route('pets.index')" icon="paw">Смотреть питомцев</x-button>
                    @endif
                </x-slot:action>
            </x-empty-state>
        @else
            <ul class="mt-8 grid gap-6 sm:grid-cols-2 lg:gap-8 xl:grid-cols-3">
                @foreach ($campaigns as $campaign)
                    <li class="flex min-w-0">
                        <x-campaign-card :campaign="$campaign" class="w-full" heading-level="2" />
                    </li>
                @endforeach

                @if ($showFund)
                    <li class="flex min-w-0">
                        <section aria-labelledby="fund-title" class="flex w-full flex-col rounded-lg bg-path-donate p-6 lg:p-8">
                            <span class="grid size-16 place-items-center rounded-pill bg-white text-tag-700">
                                <x-icon name="tenge" size="lg" />
                            </span>
                            <h2 id="fund-title" class="type-h3 mt-6">Помочь приюту без сбора</h2>
                            <p class="mt-4 text-secondary">
                                Деньги уйдут в общий фонд приюта: на корм, вакцины и счета ветклиники. Приют сам решит, что срочнее.
                            </p>
                            <div class="min-h-6 grow"></div>
                            <form method="GET" action="{{ route('donate.fund') }}" class="grid gap-4">
                                <x-select name="shelter" id="fund-shelter" aria-label="Приют, которому помочь"
                                          :options="$fundShelters->mapWithKeys(fn ($s) => [$s->slug => $s->name.', '.$s->city])->all()"
                                          :value="$shelter?->slug ?? $fundShelters->first()->slug" />
                                <x-button variant="donate" icon="heart" block>Выбрать сумму</x-button>
                            </form>
                        </section>
                    </li>
                @endif
            </ul>

            <x-pagination :paginator="$campaigns" class="mt-10" />
        @endif
    </div>
</x-layouts.app>
