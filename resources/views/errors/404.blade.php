{{-- 404 (макет 12-states): чаще всего это анкета питомца, которую убрали из каталога. --}}
<x-error-page code="404" title="Такой страницы нет" illustration="dog-graphite">
    Возможно, питомца уже забрали домой и анкету убрали из каталога. Посмотрите других — многие ждут дольше.
    <x-slot:actions>
        <x-button :href="route('pets.index')">Смотреть питомцев</x-button>
        <x-button variant="secondary" :href="route('home')">На главную</x-button>
    </x-slot:actions>
</x-error-page>
