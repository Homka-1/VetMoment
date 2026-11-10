@use('App\Enums\ApplicationStatus')
@use('App\Http\Controllers\Cabinet\ApplicationController')
@use('App\Support\LocalTime')
{{-- «Мои заявки»: заявки на питомцев с ответом приюта; активную можно отозвать. --}}
<x-layouts.cabinet title="Мои заявки">
    <div class="grid grid-cols-1 gap-6">
        <x-tabs label="Мои заявки" :items="collect(ApplicationController::TABS)->map(fn ($label, $key) => [
            'label' => $label,
            'href' => route('cabinet.applications', ['tab' => $key]),
            'active' => $tab === $key,
            'count' => $counts[$key],
        ])->values()->all()" />

        @if ($applications->isEmpty())
            <x-empty-state :title="$tab === 'active' ? 'Активных заявок нет' : 'Закрытых заявок нет'" illustration="cat-smoke" tone="sky">
                Нашли питомца, который запал в душу? Нажмите «Хочу забрать» или «Взять под опеку» в его анкете — приют перезвонит и расскажет, как познакомиться.
                <x-slot:action><x-button icon="paw" :href="route('pets.index')">Смотреть питомцев</x-button></x-slot:action>
            </x-empty-state>
        @else
            <x-table caption="Мои заявки">
                <x-slot:head>
                    <tr>
                        <x-table.th>Питомец</x-table.th>
                        <x-table.th>Что хочу</x-table.th>
                        <x-table.th>Статус</x-table.th>
                        <x-table.th>Ответ приюта</x-table.th>
                        <x-table.th align="right">Отправлена</x-table.th>
                        <x-table.th><span class="sr-only">Действия</span></x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($applications as $application)
                    <tr>
                        <td class="min-w-48">
                            <div class="flex items-center gap-3">
                                <x-pet-thumb :pet="$application->pet" size="sm" class="rounded-pill!" />
                                <div>
                                    <a href="{{ route('pets.show', $application->pet) }}" class="link font-semibold">{{ $application->pet->name }}</a>
                                    <p class="text-xs text-secondary">{{ $application->pet->shelter->name }}, {{ $application->pet->shelter->city }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap">{{ $application->type->label() }}</td>
                        <td><x-badge :status="$application->status" /></td>
                        <td class="min-w-56 max-w-72 text-secondary">{{ $application->shelter_comment ?: '—' }}</td>
                        <td class="text-right whitespace-nowrap"><time datetime="{{ LocalTime::iso($application->created_at) }}">{{ LocalTime::date($application->created_at) }}</time></td>
                        <td class="text-right">
                            @if (in_array($application->status, [ApplicationStatus::New, ApplicationStatus::InReview], true))
                                <form method="POST" action="{{ route('applications.withdraw', $application) }}">
                                    @csrf
                                    <x-button variant="ghost" size="sm">Отозвать заявку</x-button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
            <x-pagination :paginator="$applications" />
        @endif
    </div>
</x-layouts.cabinet>
