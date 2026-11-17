{{-- 429: сработал лимит запросов (вход, формы). --}}
<x-error-page code="429" title="Слишком много попыток" illustration="cat-smoke">
    Подождите минуту и попробуйте снова — так мы защищаем аккаунты от подбора пароля и формы от спама.
    <x-slot:actions>
        <x-button :href="url()->previous(route('home'))">Попробовать снова</x-button>
        <x-button variant="secondary" :href="route('home')">На главную</x-button>
    </x-slot:actions>
</x-error-page>
