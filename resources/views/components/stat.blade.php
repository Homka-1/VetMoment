@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'plain',
    'hintTone' => 'muted',
])
{{-- Цифра сводки кабинета (макеты 10, 11): подпись, крупное число, пояснение. Тон donate — только для денег. --}}
@php
    $hints = ['muted' => 'text-secondary', 'warning' => 'font-semibold text-warning-fg', 'success' => 'font-semibold text-success-fg'];
@endphp
<div {{ $attributes->class(['rounded-lg border p-5', $tone === 'donate' ? 'border-transparent bg-path-donate' : 'border-subtle bg-raised']) }}>
    <p class="text-sm text-secondary">{{ $label }}</p>
    <p class="type-number mt-2 text-primary">{{ $value }}</p>
    @if ($hint)
        <p @class(['mt-1 text-xs', $hints[$hintTone] ?? $hints['muted']])>{{ $hint }}</p>
    @endif
</div>
