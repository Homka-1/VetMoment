@props([
    'name',
    'value',
    'checked' => false,
    'id' => null,
])
@php
    $key = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $id ??= 'f-'.str_replace('.', '-', $key).'-'.$value;
    $isChecked = old($key) !== null ? (string) old($key) === (string) $value : $checked;
@endphp
<label for="{{ $id }}" {{ $attributes->only('class')->class('group inline-flex cursor-pointer items-start gap-3 py-1 text-base text-primary has-disabled:cursor-not-allowed has-disabled:text-ink-400') }}>
    <input type="radio" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" @checked($isChecked)
           {{ $attributes->except('class')->class([
               'mt-0.5 size-5 shrink-0 cursor-pointer appearance-none rounded-pill border-control border-strong bg-white transition-[border-width,border-color] duration-120',
               'group-hover:border-ink-900 checked:border-[6px] checked:border-ink-900',
               'disabled:cursor-not-allowed disabled:border-ink-300 disabled:bg-ink-100',
           ]) }}>
    <span class="leading-normal">{{ $slot }}</span>
</label>
