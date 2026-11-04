@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'id' => null,
    'hint' => null,
    'required' => false,
    'icon' => null,
    'prefix' => null,
    'suffix' => null,
    'oldInput' => true,
])
@php
    // Поле ввода 48 px, граница ink-500 1.5 px, радиус sm. Ошибка берётся из $errors по имени поля.
    $key = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $id ??= 'f-'.str_replace('.', '-', $key);
    $error = $errors->first($key);
    $current = $type === 'password' ? null : ($oldInput ? old($key, $value) : $value);
    $describedBy = $error ? "$id-error" : ($hint ? "$id-hint" : null);
@endphp
<x-form.field :label="$label" :for="$id" :hint="$hint" :error="$error" :required="$required" :class="$attributes->get('class')">
    @isset($aside)
        <x-slot:aside>{{ $aside }}</x-slot:aside>
    @endisset
    <div @class([
        'group flex h-field items-center gap-2 rounded-sm border-control bg-white px-4 transition-colors duration-120',
        'focus-within:outline-3 focus-within:outline-offset-2 focus-within:outline-focus',
        'has-disabled:border-ink-200 has-disabled:bg-ink-100',
        'border-danger-fg' => $error,
        'border-strong hover:border-ink-900' => ! $error,
    ])>
        @if ($icon)
            <x-icon :name="$icon" class="text-muted" />
        @endif
        @if ($prefix)
            <span class="text-secondary" aria-hidden="true">{{ $prefix }}</span>
        @endif
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" @if (! is_null($current)) value="{{ $current }}" @endif
               @required($required)
               @if ($error) aria-invalid="true" @endif
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
               {{ $attributes->except('class')->class('h-full w-full min-w-0 bg-transparent text-base text-primary outline-none placeholder:text-muted disabled:cursor-not-allowed disabled:text-ink-400') }}>
        @if ($suffix)
            <span class="font-semibold text-primary" aria-hidden="true">{{ $suffix }}</span>
        @endif
    </div>
</x-form.field>
