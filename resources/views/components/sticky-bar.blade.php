{{-- Липкая панель действий внизу экрана на телефоне (карточка питомца, сбор); с lg скрыта.
     Родителю нужен нижний отступ (pb-24 lg:pb-0), чтобы панель не закрывала контент. --}}
<div {{ $attributes->class('fixed inset-x-0 bottom-0 z-30 border-t border-subtle bg-white/95 px-4 pt-3 pb-[max(12px,env(safe-area-inset-bottom))] backdrop-blur lg:hidden') }}>
    <div class="mx-auto flex max-w-narrow gap-3 [&>*]:flex-1">{{ $slot }}</div>
</div>
