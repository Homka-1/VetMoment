{{-- Стартовая страница кабинета до этапа 11: приветствие, статус приюта и ссылки, которые уже работают. --}}
<x-layouts.cabinet :title="$title">
    <div class="grid gap-6">
        @isset($shelter)
            <x-shelter-moderation :shelter="$shelter" />
        @endisset

        <x-card padding="lg" class="flex flex-col gap-6 md:flex-row md:items-center">
            <x-avatar :name="$user->name" size="lg" />
            <div class="min-w-0 flex-1">
                <p class="type-h4">Здравствуйте, {{ $user->name }}</p>
                <p class="mt-1 text-secondary">Вы вошли как <strong class="font-semibold text-primary">{{ $user->login }}</strong> — {{ mb_strtolower($user->role->label()) }}.</p>
                @isset($shelter)
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-secondary">
                        Приют <strong class="font-semibold text-primary">{{ $shelter->name }}</strong>
                        <x-badge :status="$shelter->status" />
                    </div>
                @endisset
            </div>
        </x-card>

        <x-empty-state title="Разделы кабинета скоро появятся" illustration="cat-smoke" tone="sky">
            Здесь появятся разделы для работы с питомцами, заявками, сборами и выгулами. А пока можно посмотреть, кто ждёт знакомства.
            <x-slot:action>
                @can('register', \App\Models\Shelter::class)
                    <x-button variant="secondary" :href="route('cabinet.shelter-request')" icon="home">Стать приютом-партнёром</x-button>
                @endcan
                <x-button :href="\App\Support\Navigation::url('pets.index', '/pets')" icon="paw">Смотреть питомцев</x-button>
            </x-slot:action>
        </x-empty-state>
    </div>
</x-layouts.cabinet>
