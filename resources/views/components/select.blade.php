@props([
    'name',
    'options' => [],
    'label' => null,
    'value' => null,
    'id' => null,
    'hint' => null,
    'placeholder' => null,
    'required' => false,
])
@php
    $key = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $id ??= 'f-'.str_replace('.', '-', $key);
    $error = $errors->first($key);
    $current = (string) old($key, $value instanceof \BackedEnum ? $value->value : $value);
    $describedBy = $error ? "$id-error" : ($hint ? "$id-hint" : null);
@endphp
<x-form.field :label="$label" :for="$id" :hint="$hint" :error="$error" :required="$required" :class="$attributes->get('class')">
    <div class="relative">
        <select name="{{ $name }}" id="{{ $id }}" @required($required)
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $attributes->except('class')->class([
                    'h-field w-full cursor-pointer appearance-none rounded-sm border-control bg-white pr-11 pl-4 text-base text-primary transition-colors duration-120',
                    'disabled:cursor-not-allowed disabled:border-ink-200 disabled:bg-ink-100 disabled:text-ink-400',
                    'border-danger-fg' => $error,
                    'border-strong hover:border-ink-900' => ! $error,
                ]) }}>
            @if ($placeholder)
                <option value="" @selected($current === '')>{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
        <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-primary" />
    </div>
</x-form.field>
