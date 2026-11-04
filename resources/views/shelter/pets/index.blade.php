@use('App\Support\LocalTime')
{{-- Анкеты приюта (макет 10-pets): баннер модерации, поиск, фильтр по статусу, таблица с действиями. --}}
<x-layouts.cabinet title="Питомцы">
    <x-slot:actions>
        <x-button icon="plus" :href="route('shelter.pets.create')">Добавить питомца</x-button>
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-6">
        <x-shelter-moderation :shelter="$shelter" />

        <form method="GET" action="{{ route('shelter.pets.index') }}" class="flex flex-col gap-4 xl:flex-row xl:items-center" role="search">
            @if ($tab !== 'all')
                <input type="hidden" name="status" value="{{ $tab }}">
            @endif
            <x-input name="q" type="search" icon="search" :value="$q" :old-input="false" placeholder="Кличка" aria-label="Поиск по кличке" class="xl:w-80" />
            <ul class="flex flex-wrap gap-2" aria-label="Статус">
                @foreach ($tabs as $key => $item)
                    <li>
                        <x-chip :href="route('shelter.pets.index', array_filter(['status' => $key === 'all' ? null : $key, 'q' => $q ?: null]))"
                                :active="$tab === $key" :count="$item['count']">{{ $item['label'] }}</x-chip>
                    </li>
                @endforeach
            </ul>
        </form>

        @if ($pets->isEmpty())
            @if ($q !== '' || $tab !== 'all')
                <x-empty-state title="Ничего не нашли" illustration="cat-smoke" tone="sky">
                    Проверьте кличку или выберите другой статус.
                    <x-slot:action><x-button variant="secondary" :href="route('shelter.pets.index')">Показать всех</x-button></x-slot:action>
                </x-empty-state>
            @else
                <x-empty-state title="Добавьте первого питомца">
                    Анкета с фото и рассказом о характере — главное, что помогает найти питомцу дом.
                    <x-slot:action><x-button icon="plus" :href="route('shelter.pets.create')">Добавить питомца</x-button></x-slot:action>
                </x-empty-state>
            @endif
        @else
            <x-table caption="Анкеты питомцев приюта">
                <x-slot:head>
                    <tr>
                        <x-table.th>Питомец</x-table.th>
                        <x-table.th>Статус</x-table.th>
                        <x-table.th>Выгул</x-table.th>
                        <x-table.th align="right">Заявки</x-table.th>
                        <x-table.th align="right">Изменён</x-table.th>
                        <x-table.th><span class="sr-only">Действия</span></x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($pets as $pet)
                    <tr>
                        <td class="min-w-60">
                            <div class="flex items-center gap-3">
                                <span class="relative size-11 shrink-0 overflow-hidden rounded-pill bg-sky-100">
                                    @if ($pet->cover)
                                        <img src="{{ $pet->cover->thumbUrl() }}" alt="" loading="lazy" class="size-full object-cover">
                                    @else
                                        <img src="{{ asset('images/illustrations/'.($pet->species === \App\Enums\Species::Cat ? 'cat-smoke' : 'dog-caramel').'.svg') }}" alt="" class="absolute inset-x-0 bottom-0 mx-auto h-[90%]">
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <a href="{{ route('shelter.pets.edit', $pet) }}" class="font-semibold text-primary hover:underline">{{ $pet->name }}</a>
                                    <p class="text-xs text-secondary">{{ $pet->summary() }}</p>
                                </div>
                            </div>
                        </td>
                        <td><x-badge :status="$pet->status" /></td>
                        <td>
                            @if ($pet->can_walk)
                                <span class="inline-flex items-center gap-1.5 font-semibold text-meadow-700"><x-icon name="leash" size="sm" />Да</span>
                            @else
                                <span class="text-muted">Нет</span>
                            @endif
                        </td>
                        <td class="text-right tabular-nums">
                            @if ($pet->active_applications_count)
                                <a href="{{ route('shelter.applications.index') }}" class="link font-semibold">{{ $pet->active_applications_count }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <time datetime="{{ LocalTime::iso($pet->updated_at) }}">{{ LocalTime::of($pet->updated_at)->isToday() ? 'сегодня' : LocalTime::date($pet->updated_at) }}</time>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                @if (in_array($pet->status, \App\Enums\PetStatus::public(), true))
                                    <x-button variant="ghost" size="sm" icon="paw" icon-only :href="route('pets.show', $pet)" :label="'Открыть анкету '.$pet->name.' на сайте'" />
                                @endif
                                <x-button variant="ghost" size="sm" icon="edit" icon-only :href="route('shelter.pets.edit', $pet)" :label="'Редактировать '.$pet->name" />
                                @if ($pet->status !== \App\Enums\PetStatus::Archived)
                                    <x-button variant="ghost" size="sm" icon="trash" icon-only type="button" data-modal-open="archive-{{ $pet->id }}" :label="'Перенести '.$pet->name.' в архив'" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            @foreach ($pets as $pet)
                @if ($pet->status !== \App\Enums\PetStatus::Archived)
                    <x-modal id="archive-{{ $pet->id }}" title="Перенести {{ $pet->name }} в архив?">
                        Анкета пропадёт с сайта, заявки и история прогулок сохранятся. Вернуть её можно в любой момент — сменив статус.
                        <x-slot:footer>
                            <x-button variant="ghost" type="button" data-modal-close>Отмена</x-button>
                            <form method="POST" action="{{ route('shelter.pets.destroy', $pet) }}">
                                @csrf
                                @method('DELETE')
                                <x-button variant="danger" block>Перенести в архив</x-button>
                            </form>
                        </x-slot:footer>
                    </x-modal>
                @endif
            @endforeach

            <x-pagination :paginator="$pets" />
        @endif
    </div>
</x-layouts.cabinet>
