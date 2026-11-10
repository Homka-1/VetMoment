@use('App\Models\WalkTemplate')
@use('App\Support\LocalTime')
@use('App\Support\Plural')
{{-- Шаблон недели: дни × время × длительность × места × собаки на период до 8 недель. Слоты создаются сразу,
     время, где слот уже есть, пропускается. Удаление шаблона убирает будущие слоты без записей. --}}
@php
    $tz = config('app.display_timezone');
    $today = now($tz)->startOfDay();
    $chosenDays = array_map('intval', (array) old('weekdays', []));
    $chosenPets = array_map('intval', (array) old('pets', $dogs->pluck('id')->all()));
    $weeks = (int) config('walks.max_weeks_ahead', 8);
@endphp
<x-layouts.cabinet title="Шаблон недели">
    <x-slot:actions>
        <x-button variant="secondary" icon="chevron-left" :href="route('shelter.slots.index')">К слотам</x-button>
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-8">
        <x-shelter-moderation :shelter="$shelter" />

        @if ($canCreate)
            <x-card padding="lg" as="section" aria-labelledby="new-template-title">
                <h2 id="new-template-title" class="type-h4 font-semibold">Новый шаблон</h2>
                <p class="mt-1 text-sm text-secondary">Например, каждую субботу и воскресенье в 10:00. Слоты появятся в календаре сразу — на весь период, до {{ Plural::format($weeks, ['недели', 'недель', 'недель']) }} вперёд.</p>
                <x-form-errors class="mt-5" />
                <form method="POST" action="{{ route('shelter.slot-templates.store') }}" class="mt-5 grid gap-5" novalidate>
                    @csrf
                    <fieldset class="min-w-0">
                        <legend class="text-sm font-semibold">Дни недели</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach (WalkTemplate::WEEKDAY_LABELS as $n => $label)
                                <x-chip name="weekdays[]" :value="$n" :active="in_array($n, $chosenDays, true)">{{ $label }}</x-chip>
                            @endforeach
                        </div>
                        @error('weekdays')
                            <p class="mt-2 text-sm font-medium text-danger-fg" role="alert">{{ $message }}</p>
                        @enderror
                    </fieldset>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-input name="start_time" type="time" label="Начало" required value="10:00" step="{{ (int) config('walks.time_step_minutes', 15) * 60 }}" />
                        <x-select name="duration_minutes" label="Длительность" required :value="config('walks.default_duration_minutes', 60)"
                                  :options="collect($durations)->mapWithKeys(fn ($m) => [$m => intdiv($m, 60) ? trim(intdiv($m, 60).' ч '.($m % 60 ? ($m % 60).' мин' : '')) : $m.' мин'])->all()" />
                        <x-input name="capacity" type="number" label="Мест" required value="4" min="1" max="{{ config('walks.max_capacity', 20) }}" inputmode="numeric" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input name="valid_from" type="date" label="С какого дня" required :value="$today->format('Y-m-d')" />
                        <x-input name="valid_until" type="date" label="По какой день" required :value="$today->copy()->addWeeks(4)->format('Y-m-d')"
                                 :hint="'Не дальше '.LocalTime::date($today->copy()->addWeeks($weeks))" />
                    </div>
                    <x-input name="meeting_point" label="Место сбора" hint="Пусто — покажем адрес приюта." maxlength="255" />
                    <fieldset class="min-w-0">
                        <legend class="text-sm font-semibold">Собаки в слотах</legend>
                        @if ($dogs->isEmpty())
                            <p class="mt-2 text-sm text-secondary">Нет собак, готовых к прогулке, — собаку подберёте на месте.</p>
                        @else
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($dogs as $dog)
                                    <x-chip name="pets[]" :value="$dog->id" :active="in_array($dog->id, $chosenPets, true)">{{ $dog->name }}</x-chip>
                                @endforeach
                            </div>
                        @endif
                    </fieldset>
                    <div><x-button icon="plus">Создать слоты по шаблону</x-button></div>
                </form>
            </x-card>
        @endif

        <section aria-labelledby="templates-title" class="grid gap-4">
            <h2 id="templates-title" class="type-h4 font-semibold">Шаблоны приюта</h2>
            @forelse ($templates as $template)
                <x-card class="flex flex-wrap items-start justify-between gap-4">
                    <div class="grid gap-1">
                        <p class="font-semibold">
                            {{ collect($template->weekdayList())->map(fn ($d) => WalkTemplate::WEEKDAY_LABELS[$d])->implode(', ') }},
                            {{ substr((string) $template->start_time, 0, 5) }} · {{ $template->duration_minutes }} мин · {{ Plural::format($template->capacity, ['место', 'места', 'мест']) }}
                        </p>
                        <p class="text-sm text-secondary">
                            {{ LocalTime::date($template->valid_from) }} — {{ $template->valid_until ? LocalTime::date($template->valid_until) : 'без срока' }}.
                            Впереди {{ Plural::format($template->upcoming_count, ['слот', 'слота', 'слотов']) }}.
                        </p>
                        <p class="text-sm text-secondary">Собаки: {{ $template->pets->pluck('name')->implode(', ') ?: 'на выбор приюта' }}</p>
                    </div>
                    <x-button variant="secondary" size="sm" icon="trash" type="button" data-modal-open="delete-template-{{ $template->id }}">Удалить</x-button>
                    <x-modal id="delete-template-{{ $template->id }}" title="Удалить шаблон?">
                        Будущие слоты шаблона без записей исчезнут из календаря. Слоты, на которые уже записались, останутся.
                        <x-slot:footer>
                            <x-button variant="ghost" type="button" data-modal-close>Не удалять</x-button>
                            <form method="POST" action="{{ route('shelter.slot-templates.destroy', $template) }}">
                                @csrf @method('DELETE')
                                <x-button variant="danger" block>Удалить шаблон</x-button>
                            </form>
                        </x-slot:footer>
                    </x-modal>
                </x-card>
            @empty
                <p class="rounded-md border border-dashed border-ink-300 p-6 text-center text-secondary">Шаблонов пока нет — слоты создаются по одному.</p>
            @endforelse
        </section>
    </div>
</x-layouts.cabinet>
