@use('App\Enums\PetSize')
@use('App\Services\PetCatalog')
@use('App\Support\Plural')
{{-- Каталог питомцев (макет 02): фильтры слева (на телефоне — нижний лист), поиск, сортировка, сетка карточек.
     Одна GET-форма #pet-filters; поиск и сортировка вне её привязаны атрибутом form. --}}
@php
    $activeCount = count($chips);
    $titleCount = Plural::format($total, ['ищет дом', 'ищут дом', 'ищут дом']);
@endphp
<x-layouts.app title="Питомцы из приютов" description="Собаки и кошки из приютов Казахстана, которые ищут дом: фильтры по виду, возрасту, размеру и городу.">
    <div class="page-container pt-6 pb-16 lg:pt-8 lg:pb-24">
        <x-breadcrumbs :items="['Главная' => route('home'), 'Питомцы' => null]" />

        <div class="mt-4 flex flex-wrap items-baseline gap-x-4 gap-y-1 lg:mt-6">
            <h1 class="type-h1">Питомцы</h1>
            <p class="text-secondary">{{ $titleCount }}</p>
        </div>

        <div class="mt-6 lg:mt-10 lg:grid lg:grid-cols-[var(--spacing-filters)_minmax(0,1fr)] lg:gap-8">
            {{-- Фильтры: на десктопе — колонка, на телефоне — нижний лист (<dialog>). --}}
            <dialog id="pet-filters-sheet" aria-labelledby="pet-filters-title"
                    class="vm-sheet m-0 mt-auto max-h-[92dvh] w-full max-w-none overflow-y-auto rounded-t-lg bg-white p-0 text-primary backdrop:bg-ink-900/50
                           lg:static lg:mt-0 lg:block lg:max-h-none lg:overflow-visible lg:rounded-none lg:bg-transparent">
                <form id="pet-filters" method="GET" action="{{ route('pets.index') }}" data-autosubmit role="search" aria-label="Фильтры питомцев">
                    <div class="sticky top-0 z-10 flex items-center justify-between bg-white px-4 pt-5 pb-2 lg:hidden">
                        <h2 id="pet-filters-title" class="type-h3">Фильтры</h2>
                        <button type="button" data-modal-close class="-mr-2 grid size-control place-items-center rounded-pill hover:bg-ink-100" aria-label="Закрыть фильтры">
                            <x-icon name="x" size="lg" />
                        </button>
                    </div>

                    <div class="grid gap-7 px-4 pt-3 pb-6 lg:gap-6 lg:p-0">
                        <fieldset>
                            <legend class="type-label mb-3">Вид</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach (PetCatalog::SPECIES_LABELS as $value => $label)
                                    <x-chip name="species[]" :value="$value" :active="in_array($value, $filters['species'], true)">{{ $label }}</x-chip>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="type-label mb-3">Пол</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach (PetCatalog::SEX_LABELS as $value => $label)
                                    <x-chip name="sex[]" :value="$value" :active="in_array($value, $filters['sex'], true)">{{ $label }}</x-chip>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="type-label mb-3">Возраст</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach (PetCatalog::AGES as $value => $age)
                                    <x-chip name="age[]" :value="$value" :active="in_array($value, $filters['age'], true)">{{ $age['label'] }}</x-chip>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="type-label mb-3">Размер</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach (PetSize::options() as $value => $label)
                                    <x-chip name="size[]" :value="$value" :active="in_array($value, $filters['size'], true)">{{ $label }}</x-chip>
                                @endforeach
                            </div>
                        </fieldset>

                        <x-select name="city" label="Город" :options="$cities->combine($cities)->all()" :value="$filters['city']" placeholder="Все города" />
                        <x-select name="shelter" label="Приют" :options="$shelters->all()" :value="$filters['shelter']" placeholder="Все приюты" />

                        <div class="grid gap-2">
                            <x-toggle name="walk" :checked="$filters['walk']">Готов к выгулу</x-toggle>
                            <x-toggle name="guardianship" :checked="$filters['guardianship']">Можно под опеку</x-toggle>
                        </div>

                        <div class="hidden flex-wrap items-center gap-4 lg:flex">
                            <x-button size="sm" data-autosubmit-fallback>Показать</x-button>
                            @if ($activeCount)
                                <a href="{{ route('pets.index') }}" class="link text-sm font-semibold">Сбросить фильтры</a>
                            @endif
                        </div>
                    </div>

                    <div class="sticky bottom-0 flex items-center gap-3 border-t border-subtle bg-white px-4 pt-3 pb-[max(12px,env(safe-area-inset-bottom))] lg:hidden">
                        <x-button variant="ghost" :href="route('pets.index')" class="flex-1">Сбросить</x-button>
                        <x-button class="flex-[2]">Показать питомцев</x-button>
                    </div>
                </form>
            </dialog>

            <div class="min-w-0">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <x-input name="q" type="search" icon="search" :value="$filters['q']" form="pet-filters" :old-input="false"
                             placeholder="Кличка питомца" aria-label="Поиск по кличке" maxlength="50" class="flex-1" />
                    <div class="flex items-center gap-3">
                        <x-button variant="secondary" icon="filter" data-modal-open="pet-filters-sheet" aria-haspopup="dialog" class="flex-1 lg:hidden">
                            Фильтры @if ($activeCount)<span class="tabular-nums">· {{ $activeCount }}</span>@endif
                        </x-button>
                        <label for="f-sort" class="hidden text-sm text-secondary lg:block">Сначала:</label>
                        <x-select name="sort" :options="PetCatalog::SORTS" :value="$filters['sort']" form="pet-filters" aria-label="Сортировка" class="flex-1 sm:w-60 sm:flex-none" />
                    </div>
                </div>

                @if ($chips)
                    <ul class="mt-4 flex flex-wrap gap-2" aria-label="Выбранные фильтры">
                        @foreach ($chips as $chip)
                            <li>
                                <a href="{{ $chip['href'] }}" class="inline-flex h-control-sm items-center gap-1.5 rounded-pill bg-ink-100 pr-2.5 pl-3.5 text-sm font-medium text-primary transition-colors hover:bg-ink-200"
                                   aria-label="Убрать фильтр «{{ $chip['label'] }}»">
                                    {{ $chip['label'] }}<x-icon name="x" size="sm" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <h2 class="sr-only" aria-live="polite">Найдено: {{ Plural::format($pets->total(), ['питомец', 'питомца', 'питомцев']) }}</h2>

                @if ($pets->isEmpty())
                    <x-empty-state class="mt-6" title="Никого не нашли" illustration="cat-smoke" tone="sky">
                        Попробуйте убрать часть фильтров — например, город или возраст. Питомцы появляются в каталоге каждую неделю.
                        <x-slot:action>
                            <x-button variant="secondary" :href="route('pets.index')">Сбросить фильтры</x-button>
                        </x-slot:action>
                    </x-empty-state>
                @else
                    <ul class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 lg:gap-8">
                        @foreach ($pets as $pet)
                            <li class="flex min-w-0">
                                <x-pet-card :pet="$pet" class="w-full" heading-level="3" responsive
                                            :favorite-url="route('pets.favorite', $pet)" :favorited="in_array($pet->id, $favoriteIds, true)" />
                            </li>
                        @endforeach
                    </ul>

                    <x-pagination :paginator="$pets" class="mt-10" />
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
