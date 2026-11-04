@props([
    'name' => 'amount',
    'presets' => [1000, 2000, 5000, 10000],
    'value' => null,
    'label' => 'Сумма пожертвования',
    'min' => 200,
    'max' => 5000000,
])
@php
    // Пресеты сумм (в тенге) + «Другая сумма». Форма отправляет одно поле $name в тенге:
    // выбранный пресет кладётся в поле «Другая сумма» скриптом, без JS работает само поле.
    $current = old($name, $value);
    $display = is_numeric($current) ? number_format((int) $current, 0, ',', ' ') : $current;
    $error = $errors->first($name);
    $id = 'f-'.$name;
@endphp
<fieldset {{ $attributes->class('grid gap-3') }} data-amount-picker>
    <legend class="type-label mb-3 text-primary">{{ $label }}</legend>
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
        @foreach ($presets as $preset)
            <label class="relative grid h-control-lg cursor-pointer place-items-center rounded-sm border-control border-ink-300 bg-white font-display text-base font-semibold text-primary transition-colors duration-120 hover:border-ink-900 has-checked:border-ink-900 has-checked:bg-tag-100 has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus">
                <input type="radio" name="{{ $name }}_preset" value="{{ $preset }}" class="sr-only" data-amount-preset
                       @checked((int) $current === $preset)>
                {{ \App\Support\Money::format($preset * 100) }}
            </label>
        @endforeach
    </div>
    <x-input :name="$name" type="text" inputmode="numeric" :id="$id" label="Другая сумма" suffix="₸" :value="$display"
             :hint="'От '.\App\Support\Money::format($min * 100).' до '.\App\Support\Money::format($max * 100)"
             data-amount-input autocomplete="off" placeholder="0" />
</fieldset>
