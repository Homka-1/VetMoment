@use('App\Enums\ApplicationStatus')
@use('App\Support\LocalTime')
@use('App\Support\Phone')
{{-- Заявки на питомцев: администратор только смотрит, решения принимает приют. --}}
<x-layouts.cabinet title="Заявки">
    <div class="grid grid-cols-1 gap-6">
        <form method="GET" action="{{ route('admin.applications.index') }}" data-autosubmit class="max-w-64">
            <x-select name="status" label="Статус" :options="collect(ApplicationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" :value="$status?->value" placeholder="Все статусы" />
            <x-button class="mt-3" variant="secondary" size="sm" data-autosubmit-fallback>Показать</x-button>
        </form>

        @if ($applications->isEmpty())
            <x-empty-state title="Заявок нет" illustration="cat-smoke" tone="sky">В этом статусе заявок пока нет.</x-empty-state>
        @else
            <x-table caption="Заявки на питомцев">
                <x-slot:head>
                    <tr>
                        <x-table.th>Питомец</x-table.th>
                        <x-table.th>Кто</x-table.th>
                        <x-table.th>Что хочет</x-table.th>
                        <x-table.th>Статус</x-table.th>
                        <x-table.th align="right">Получена</x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($applications as $application)
                    <tr>
                        <td class="min-w-48">
                            <div class="flex items-center gap-3">
                                <x-pet-thumb :pet="$application->pet" size="sm" class="rounded-pill!" />
                                <div>
                                    <p class="font-semibold">{{ $application->pet->name }}</p>
                                    <p class="text-xs text-secondary">{{ $application->pet->shelter->name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="min-w-48">
                            <p class="font-semibold">{{ $application->contact_name }}</p>
                            <p class="text-xs text-secondary">{{ Phone::format($application->contact_phone) }} · {{ $application->city }}</p>
                        </td>
                        <td class="whitespace-nowrap">{{ $application->type->label() }}</td>
                        <td><x-badge :status="$application->status" /></td>
                        <td class="text-right whitespace-nowrap">{{ LocalTime::date($application->created_at) }}</td>
                    </tr>
                @endforeach
            </x-table>
            <x-pagination :paginator="$applications" />
        @endif
    </div>
</x-layouts.cabinet>
