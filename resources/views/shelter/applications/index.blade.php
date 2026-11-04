@use('App\Http\Controllers\Shelter\ApplicationController')
@use('App\Support\LocalTime')
@use('App\Support\Phone')
{{-- Входящие заявки «Забрать домой» / «Взять под опеку»: вкладки по стадиям, таблица, переход к заявке. --}}
<x-layouts.cabinet title="Заявки">
    <div class="grid grid-cols-1 gap-6">
        <x-tabs label="Стадии заявок" :items="collect(ApplicationController::TABS)->map(fn ($t, $key) => [
            'label' => $t['label'],
            'href' => route('shelter.applications.index', ['status' => $key]),
            'active' => $tab === $key,
            'count' => $counts[$key],
        ])->values()->all()" />

        @if ($applications->isEmpty())
            <x-empty-state :title="$tab === 'new' ? 'Новых заявок нет' : 'Здесь пока пусто'" illustration="cat-smoke" tone="sky">
                {{ $tab === 'new'
                    ? 'Заявки появятся, когда кто-то нажмёт «Хочу забрать» или «Взять под опеку» в анкете. Подробные анкеты с фото получают их чаще.'
                    : 'Заявки попадут сюда, когда вы возьмёте их в работу или закроете.' }}
                <x-slot:action><x-button variant="secondary" icon="paw" :href="route('shelter.pets.index')">К анкетам</x-button></x-slot:action>
            </x-empty-state>
        @else
            <x-table caption="Заявки на питомцев">
                <x-slot:head>
                    <tr>
                        <x-table.th>Питомец</x-table.th>
                        <x-table.th>Кто</x-table.th>
                        <x-table.th>Что хочет</x-table.th>
                        <x-table.th>Статус</x-table.th>
                        <x-table.th align="right">Получена</x-table.th>
                        <x-table.th><span class="sr-only">Действия</span></x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($applications as $application)
                    <tr>
                        <td class="min-w-44">
                            <div class="flex items-center gap-3">
                                <span class="relative size-10 shrink-0 overflow-hidden rounded-pill bg-sky-100">
                                    @if ($application->pet->cover)
                                        <img src="{{ $application->pet->cover->thumbUrl() }}" alt="" loading="lazy" class="size-full object-cover">
                                    @endif
                                </span>
                                <span class="font-semibold">{{ $application->pet->name }}</span>
                            </div>
                        </td>
                        <td class="min-w-48">
                            <p class="font-semibold">{{ $application->contact_name }}</p>
                            <p class="text-xs text-secondary">{{ Phone::format($application->contact_phone) }} · {{ $application->city }}</p>
                        </td>
                        <td class="whitespace-nowrap">{{ $application->type->label() }}</td>
                        <td><x-badge :status="$application->status" /></td>
                        <td class="text-right whitespace-nowrap"><time datetime="{{ LocalTime::iso($application->created_at) }}">{{ LocalTime::date($application->created_at) }}</time></td>
                        <td class="text-right">
                            <x-button variant="secondary" size="sm" :href="route('shelter.applications.show', $application)">Открыть</x-button>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            <x-pagination :paginator="$applications" />
        @endif
    </div>
</x-layouts.cabinet>
