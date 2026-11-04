@use('App\Support\LocalTime')
@props([
    'days' => [],
    'name' => 'day',
    'selected' => null,
    'label' => 'Выберите день',
])
{{-- Выбор дня недели (UI-кит, артборд 03). $days — список ['date' => Carbon, 'available' => bool].
     Точка — в этот день есть свободные слоты. Скрипт (resources/js/ui/day-picker.js) показывает панель
     [data-day-panel="Y-m-d"] выбранного дня; без JS форма отправляет выбранную дату. --}}
<fieldset {{ $attributes->class('min-w-0') }} data-day-picker>
    <legend class="sr-only">{{ $label }}</legend>
    <div class="-mx-1 flex gap-2 overflow-x-auto px-1 py-1">
        @foreach ($days as $day)
            @php
                $local = LocalTime::of($day['date']);
                $key = $local->format('Y-m-d');
                $available = $day['available'] ?? false;
            @endphp
            <label class="group relative grid h-[72px] w-16 shrink-0 cursor-pointer place-items-center content-center gap-0.5 rounded-sm border-control border-ink-300 bg-white text-primary transition-colors duration-120 hover:border-ink-900 has-checked:border-ink-900 has-checked:bg-ink-900 has-checked:text-white has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus">
                <input type="radio" name="{{ $name }}" value="{{ $key }}" class="sr-only" data-day-input
                       @checked($selected === $key)>
                <span class="text-xs font-medium opacity-80">{{ LocalTime::weekdayShort($local) }}</span>
                <span @class(['font-display text-lg font-semibold tabular-nums', 'text-ink-400 group-has-checked:text-white' => ! $available])>{{ $local->day }}</span>
                <span @class(['size-1.5 rounded-pill', 'bg-meadow-500 group-has-checked:bg-tag-500' => $available, 'bg-transparent' => ! $available])></span>
                <span class="sr-only">{{ LocalTime::date($local) }}{{ $available ? ', есть свободное время' : ', выгулов нет' }}</span>
            </label>
        @endforeach
    </div>
</fieldset>
