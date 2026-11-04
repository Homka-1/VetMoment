{{-- Избранное волонтёра: питомцы, отмеченные сердечком в каталоге. --}}
<x-layouts.cabinet title="Избранное">
    @if ($pets->isEmpty())
        <x-empty-state title="Здесь будут питомцы, которых вы отметили" illustration="dog-caramel" tone="tag">
            Нажмите на сердечко на карточке питомца — он появится в этом списке, и его будет легко найти снова.
            <x-slot:action>
                <x-button :href="route('pets.index')" icon="paw">Смотреть питомцев</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <ul class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-3 xl:gap-6">
            @foreach ($pets as $pet)
                <li class="flex min-w-0">
                    <x-pet-card :pet="$pet" class="w-full" responsive :favorite-url="route('pets.favorite', $pet)" favorited heading-level="2" />
                </li>
            @endforeach
        </ul>
        <x-pagination :paginator="$pets" class="mt-8" />
    @endif
</x-layouts.cabinet>
