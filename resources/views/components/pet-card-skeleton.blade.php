{{-- Скелетон карточки питомца на время загрузки (UI-кит, артборд 04) --}}
<div {{ $attributes->class('flex flex-col rounded-lg border border-subtle bg-raised p-2') }} aria-hidden="true">
    <div class="vm-skeleton aspect-[4/3.4] rounded-md"></div>
    <div class="grid gap-3 px-3 pt-5 pb-4">
        <div class="vm-skeleton h-5 w-1/2 rounded-pill"></div>
        <div class="vm-skeleton h-3.5 w-3/4 rounded-pill"></div>
        <div class="flex gap-2"><div class="vm-skeleton h-6 w-20 rounded-pill"></div><div class="vm-skeleton h-6 w-16 rounded-pill"></div></div>
        <div class="vm-skeleton mt-2 h-3.5 w-2/3 rounded-pill"></div>
    </div>
</div>
