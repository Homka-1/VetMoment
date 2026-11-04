@props([
    'name',
    'value' => '1',
    'checked' => false,
    'id' => null,
    'hint' => null,
])
@php
    // Чекбокс 20 px, радиус xs; зона клика — вся строка с подписью (слот).
    $key = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $id ??= 'f-'.str_replace('.', '-', $key).(str_ends_with($name, '[]') ? '-'.$value : '');
    $error = str_ends_with($name, '[]') ? null : $errors->first($key);
    $isChecked = old() ? in_array((string) $value, array_map('strval', (array) old($key, [])), true) || old($key) === (string) $value : $checked;
@endphp
<div {{ $attributes->only('class')->class('grid gap-1') }}>
    <label for="{{ $id }}" class="group inline-flex cursor-pointer items-start gap-3 py-1 text-base text-primary has-disabled:cursor-not-allowed has-disabled:text-ink-400">
        <span class="relative mt-0.5 grid size-5 shrink-0 place-items-center">
            <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" @checked($isChecked)
                   @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
                   {{ $attributes->except('class')->class([
                       'peer size-5 cursor-pointer appearance-none rounded-xs border-control bg-white transition-colors duration-120',
                       'checked:border-ink-900 checked:bg-ink-900',
                       'disabled:cursor-not-allowed disabled:border-ink-300 disabled:bg-ink-100 disabled:checked:border-ink-300 disabled:checked:bg-ink-300',
                       'border-danger-fg' => $error,
                       'border-strong group-hover:border-ink-900' => ! $error,
                   ]) }}>
            <x-icon name="check" size="sm" class="pointer-events-none absolute text-white opacity-0 peer-checked:opacity-100" />
        </span>
        <span class="leading-normal">{{ $slot }}</span>
    </label>
    @if ($error)
        <p id="{{ $id }}-error" class="flex items-start gap-1.5 pl-8 text-sm leading-snug font-medium text-danger-fg">
            <x-icon name="alert-circle" size="sm" class="mt-px" /><span>{{ $error }}</span>
        </p>
    @elseif ($hint)
        <p class="type-caption pl-8 text-muted">{{ $hint }}</p>
    @endif
</div>
