{{-- Flash-сообщения сессии → тосты в правом верхнем углу (на телефоне — сверху на всю ширину).
     session('status') — успех, session('error') — ошибка, session('info') — информация;
     session('toast') = ['tone' => …, 'title' => …, 'text' => …] — полный вариант. --}}
@php
    $toasts = [];
    if ($toast = session('toast')) {
        $toasts[] = $toast;
    }
    if ($status = session('status')) {
        $toasts[] = ['tone' => 'success', 'title' => $status];
    }
    if ($error = session('error')) {
        $toasts[] = ['tone' => 'danger', 'title' => $error];
    }
    if ($info = session('info')) {
        $toasts[] = ['tone' => 'info', 'title' => $info];
    }
@endphp
<div id="toasts" class="pointer-events-none fixed inset-x-0 top-0 z-50 flex flex-col items-end gap-3 p-4 sm:top-4 sm:right-4 sm:left-auto sm:w-[400px] sm:p-0" aria-live="polite">
    @foreach ($toasts as $toast)
        <x-toast :tone="$toast['tone'] ?? 'success'" :title="$toast['title']">{{ $toast['text'] ?? '' }}</x-toast>
    @endforeach
</div>

{{-- Шаблон для тостов из скрипта: VetMoment.toast({ title, text }) --}}
<template id="toast-template">
    <x-toast tone="success" title="">
        <span data-toast-text></span>
    </x-toast>
</template>
