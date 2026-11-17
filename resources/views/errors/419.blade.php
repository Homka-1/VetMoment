{{-- 419: истёк CSRF-токен — форму держали открытой слишком долго. --}}
<x-error-page code="419" title="Форма устарела" illustration="dog-caramel">
    Страница была открыта слишком долго, и защитный ключ формы истёк. Вернитесь назад, обновите страницу
    и отправьте форму ещё раз.
    <x-slot:actions>
        <x-button :href="url()->previous(route('home'))">Вернуться к форме</x-button>
        <x-button variant="secondary" :href="route('home')">На главную</x-button>
    </x-slot:actions>
</x-error-page>
