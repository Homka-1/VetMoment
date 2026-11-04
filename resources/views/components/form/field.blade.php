@props([
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'counter' => null,
])
{{-- Обёртка поля: подпись над полем, подсказка или ошибка под ним (UI-кит, «Поля ввода»).
     Слот `aside` — справа от подписи (например, «Забыли пароль?»). --}}
<div {{ $attributes->class('grid content-start gap-1.5') }}>
    @if ($label || isset($aside))
        <div class="flex items-baseline justify-between gap-3">
            @if ($label)
                <label for="{{ $for }}" class="type-label text-primary">
                    {{ $label }}@if ($required)<span class="text-danger-fg" aria-hidden="true"> *</span>@endif
                </label>
            @endif
            @isset($aside)
                <div class="type-label">{{ $aside }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}

    @if ($error || $hint || $counter)
        <div class="flex items-start justify-between gap-3">
            @if ($error)
                <p id="{{ $for }}-error" class="flex items-start gap-1.5 text-sm leading-snug font-medium text-danger-fg">
                    <x-icon name="alert-circle" size="sm" class="mt-px" />
                    <span>{{ $error }}</span>
                </p>
            @elseif ($hint)
                <p id="{{ $for }}-hint" class="type-caption text-muted">{{ $hint }}</p>
            @endif
            @if ($counter)
                <p class="type-caption ml-auto whitespace-nowrap text-muted tabular-nums" data-counter-for="{{ $for }}" aria-live="polite">{{ $counter }}</p>
            @endif
        </div>
    @endif
</div>
