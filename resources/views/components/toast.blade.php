@props([
    'tone' => 'success',
    'title',
    'autohide' => null,
])
@php
    // Тост (UI-кит, артборд 05): повторяет глагол кнопки — «Записаться» → «Вы записаны на выгул».
    // Ошибки не скрываются сами — их надо прочитать.
    $tones = [
        'success' => ['icon' => 'check-circle', 'ring' => 'bg-success-bg text-success-fg'],
        'danger' => ['icon' => 'alert-circle', 'ring' => 'bg-danger-bg text-danger-fg'],
        'info' => ['icon' => 'info', 'ring' => 'bg-info-bg text-info-fg'],
        'warning' => ['icon' => 'alert-triangle', 'ring' => 'bg-warning-bg text-warning-fg'],
    ];
    $t = $tones[$tone] ?? $tones['info'];
    $autohide ??= $tone !== 'danger';
@endphp
<div {{ $attributes->class('vm-toast pointer-events-auto flex w-full items-start gap-3 rounded-md border border-subtle bg-raised p-4 shadow-lg') }}
     data-toast @if ($autohide) data-autohide="6000" @endif
     role="{{ $tone === 'danger' ? 'alert' : 'status' }}">
    <span @class(['grid size-9 shrink-0 place-items-center rounded-pill', $t['ring']])>
        <x-icon :name="$t['icon']" />
    </span>
    <div class="min-w-0 flex-1 pt-1">
        <p class="leading-snug font-semibold" data-toast-title>{{ $title }}</p>
        @if ($slot->isNotEmpty())
            <p class="mt-1 text-sm text-secondary">{{ $slot }}</p>
        @endif
    </div>
    <button type="button" data-toast-close class="-m-1 grid size-9 shrink-0 place-items-center rounded-pill text-ink-700 hover:bg-ink-100" aria-label="Закрыть уведомление">
        <x-icon name="x" size="sm" />
    </button>
</div>
