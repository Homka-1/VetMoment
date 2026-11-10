{{-- Поля слота: дата, начало и конец (время Казахстана), места, место сбора, собаки.
     $values = ['date' => 'Y-m-d', 'start_time' => 'H:i', 'end_time' => 'H:i', 'capacity' => int, 'meeting_point' => ?, 'pets' => [id]]; $dogs; $wide — форма на всю ширину. --}}
@php
    $step = (int) config('walks.time_step_minutes', 15) * 60;
    $chosenPets = array_map('intval', (array) old('pets', $values['pets'] ?? []));
@endphp
<div @class(['grid gap-4 sm:grid-cols-2', 'lg:grid-cols-[minmax(0,1.6fr)_repeat(3,minmax(0,1fr))]' => $wide ?? false])>
    <x-input name="date" type="date" label="Дата" required :value="$values['date'] ?? ''" class="sm:col-span-2 lg:col-span-1" />
    <x-input name="start_time" type="time" label="Начало" required :value="$values['start_time'] ?? ''" step="{{ $step }}" />
    <x-input name="end_time" type="time" label="Конец" required :value="$values['end_time'] ?? ''" step="{{ $step }}" />
    <x-input name="capacity" type="number" label="Мест" required :value="$values['capacity'] ?? 4" min="1" max="{{ config('walks.max_capacity', 20) }}" inputmode="numeric" />
</div>
<x-input name="meeting_point" label="Место сбора" :value="$values['meeting_point'] ?? ''" hint="Например: у ворот приюта. Пусто — покажем адрес приюта." maxlength="255" />
<fieldset class="min-w-0">
    <legend class="text-sm font-semibold">Собаки в слоте</legend>
    @if ($dogs->isEmpty())
        <p class="mt-2 text-sm text-secondary">Нет собак, готовых к прогулке. Отметьте «Можно на выгул» в анкете — или оставьте пусто, тогда собаку подберёте на месте.</p>
    @else
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach ($dogs as $dog)
                <x-chip name="pets[]" :value="$dog->id" :active="in_array($dog->id, $chosenPets, true)">{{ $dog->name }}</x-chip>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-muted">Не выбрали ни одной — волонтёр запишется без выбора, собаку подберёте на месте.</p>
    @endif
    @error('pets')
        <p class="mt-2 text-sm font-medium text-danger-fg" role="alert">{{ $message }}</p>
    @enderror
    @error('pets.*')
        <p class="mt-2 text-sm font-medium text-danger-fg" role="alert">{{ $message }}</p>
    @enderror
</fieldset>
