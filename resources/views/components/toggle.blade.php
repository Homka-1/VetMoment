@props([
    'name',
    'value' => '1',
    'checked' => false,
    'id' => null,
])
@php
    // Переключатель: чекбокс с role="switch" — для настроек, действующих сразу (анонимно, фильтр).
    $key = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $id ??= 'f-'.str_replace('.', '-', $key);
    $isChecked = old() ? (bool) old($key) : $checked;
@endphp
<label for="{{ $id }}" {{ $attributes->only('class')->class('group inline-flex cursor-pointer items-center gap-3 py-1 text-base text-primary has-disabled:cursor-not-allowed has-disabled:text-ink-400') }}>
    <span class="relative inline-flex h-7 w-11 shrink-0 items-center">
        <input type="checkbox" role="switch" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" @checked($isChecked)
               {{ $attributes->except('class')->class('peer absolute inset-0 size-full cursor-pointer appearance-none rounded-pill bg-ink-200 transition-colors duration-200 checked:bg-ink-900 disabled:cursor-not-allowed disabled:opacity-60') }}>
        <span class="pointer-events-none absolute left-1 grid size-5 place-items-center rounded-pill bg-white shadow-sm transition-transform duration-200 ease-out peer-checked:translate-x-4">
            <x-icon name="check" size="sm" class="size-3 text-ink-900 opacity-0 transition-opacity peer-checked:opacity-100 [:checked~*>&]:opacity-100" />
        </span>
    </span>
    <span>{{ $slot }}</span>
</label>
