@props([
    'id',
    'title',
    'size' => 'md',
])
{{-- Модальное окно на <dialog>: фокус внутри, Esc закрывает, фон — ink с прозрачностью.
     Открыть: <button data-modal-open="{{ $id }}">; закрыть: [data-modal-close] или клик по фону.
     Только для подтверждения необратимых действий (UI-кит, артборд 05). Слот footer — кнопки. --}}
<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-title"
        {{ $attributes->class([
            'vm-dialog m-auto w-[calc(100%-32px)] rounded-lg bg-raised p-0 text-primary shadow-lg backdrop:bg-transparent',
            'max-w-[480px]' => $size === 'md',
            'max-w-[640px]' => $size === 'lg',
        ]) }}>
    <div class="grid gap-4 p-6 md:p-7">
        <div class="flex items-start justify-between gap-4">
            <h2 id="{{ $id }}-title" class="type-h4 font-semibold">{{ $title }}</h2>
            <button type="button" data-modal-close class="-mt-1 -mr-2 grid size-10 shrink-0 place-items-center rounded-pill text-ink-700 hover:bg-ink-100" aria-label="Закрыть">
                <x-icon name="x" />
            </button>
        </div>
        <div class="text-secondary">{{ $slot }}</div>
        @isset($footer)
            <div class="mt-2 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">{{ $footer }}</div>
        @endisset
    </div>
</dialog>
