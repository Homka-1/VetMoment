@props([
    'name',
    'label' => null,
    'value' => null,
    'id' => null,
    'hint' => null,
    'required' => false,
    'rows' => 4,
    'maxlength' => null,
])
@php
    $key = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $id ??= 'f-'.str_replace('.', '-', $key);
    $error = $errors->first($key);
    $current = (string) old($key, $value);
    $describedBy = $error ? "$id-error" : ($hint ? "$id-hint" : null);
    // Счётчик символов обновляется скриптом (resources/js/ui/forms.js), без JS виден лимит.
    $counter = $maxlength ? mb_strlen($current).' / '.$maxlength : null;
@endphp
<x-form.field :label="$label" :for="$id" :hint="$hint" :error="$error" :required="$required" :counter="$counter" :class="$attributes->get('class')">
    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}" @required($required)
              @if ($maxlength) maxlength="{{ $maxlength }}" data-counter @endif
              @if ($error) aria-invalid="true" @endif
              @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
              {{ $attributes->except('class')->class([
                  'block w-full resize-y rounded-sm border-control bg-white px-4 py-3 text-base text-primary transition-colors duration-120 placeholder:text-muted',
                  'disabled:cursor-not-allowed disabled:border-ink-200 disabled:bg-ink-100 disabled:text-ink-400',
                  'border-danger-fg' => $error,
                  'border-strong hover:border-ink-900' => ! $error,
              ]) }}>{{ $current }}</textarea>
</x-form.field>
