@use('App\Http\Controllers\Admin\PetController')
@use('App\Support\LocalTime')
{{-- Анкеты: модерация. «Скрыть» убирает анкету с сайта (приют её по-прежнему видит), «Вернуть» — возвращает. --}}
<x-layouts.cabinet title="Анкеты">
    <div class="grid grid-cols-1 gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <x-tabs label="Анкеты" class="grow" :items="collect(PetController::TABS)->map(fn ($label, $key) => [
                'label' => $label,
                'href' => route('admin.pets.index', ['tab' => $key, 'q' => $search ?: null]),
                'active' => $tab === $key,
                'count' => $counts[$key],
            ])->values()->all()" />
            <form method="GET" action="{{ route('admin.pets.index') }}" role="search" class="flex gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <x-input name="q" type="search" :value="$search" placeholder="Кличка" aria-label="Поиск по кличке" />
                <x-button variant="secondary">Найти</x-button>
            </form>
        </div>

        @if ($pets->isEmpty())
            <x-empty-state :title="$tab === 'hidden' ? 'Скрытых анкет нет' : 'Анкеты не найдены'" illustration="cat-smoke" tone="sky">
                {{ $tab === 'hidden' ? 'Анкеты, которые вы скроете, появятся здесь — их можно вернуть на сайт.' : 'Попробуйте другую кличку.' }}
            </x-empty-state>
        @else
            <x-table caption="Анкеты питомцев">
                <x-slot:head>
                    <tr>
                        <x-table.th>Питомец</x-table.th>
                        <x-table.th>Приют</x-table.th>
                        <x-table.th>Статус</x-table.th>
                        <x-table.th>Изменена</x-table.th>
                        <x-table.th><span class="sr-only">Действия</span></x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($pets as $pet)
                    <tr>
                        <td class="min-w-48">
                            <div class="flex items-center gap-3">
                                <x-pet-thumb :pet="$pet" size="sm" class="rounded-pill!" />
                                <div>
                                    <a href="{{ route('pets.show', $pet) }}" class="link font-semibold">{{ $pet->name }}</a>
                                    <p class="text-xs text-secondary">{{ $pet->summary() }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="min-w-40">{{ $pet->shelter->name }}<p class="text-xs text-secondary">{{ $pet->shelter->city }}</p></td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <x-badge :status="$pet->status" />
                                @if ($pet->is_hidden)<x-badge tone="danger">Скрыта с сайта</x-badge>@endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap">{{ LocalTime::date($pet->updated_at) }}</td>
                        <td class="text-right">
                            @if ($pet->is_hidden)
                                <form method="POST" action="{{ route('admin.pets.restore', $pet) }}">@csrf<x-button variant="secondary" size="sm">Вернуть</x-button></form>
                            @else
                                <form method="POST" action="{{ route('admin.pets.hide', $pet) }}">@csrf<x-button variant="secondary" size="sm">Скрыть</x-button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
            <x-pagination :paginator="$pets" />
        @endif
    </div>
</x-layouts.cabinet>
