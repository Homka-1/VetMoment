@use('App\Enums\ApplicationStatus')
@use('App\Http\Controllers\Shelter\ApplicationController')
@use('App\Support\LocalTime')
@use('App\Support\Phone')
{{-- Заявка на питомца: ответы человека, контакты и решение приюта. --}}
@php
    $pet = $application->pet;
    $actions = ApplicationController::ACTIONS;
@endphp
<x-layouts.cabinet :title="'Заявка на питомца '.$pet->name" :heading="'Заявка: '.$pet->name">
    <div class="grid grid-cols-1 gap-6">
        <x-breadcrumbs :items="['Заявки' => route('shelter.applications.index', ['status' => match (true) {
            $application->status === ApplicationStatus::New => 'new',
            $application->status->isActive() => 'in_review',
            default => 'closed',
        }]), $pet->name => null]" />
        <x-form-errors />

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <x-card padding="lg" class="grid content-start gap-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="type-h4">{{ $application->type->label() }}</p>
                    <x-badge :status="$application->status" />
                </div>

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm text-muted">Имя</dt><dd class="font-semibold">{{ $application->contact_name }}</dd></div>
                    <div><dt class="text-sm text-muted">Телефон</dt><dd><a href="tel:{{ $application->contact_phone }}" class="link font-semibold">{{ Phone::format($application->contact_phone) }}</a></dd></div>
                    <div><dt class="text-sm text-muted">Город</dt><dd>{{ $application->city }}</dd></div>
                    @if ($application->living_conditions)
                        <div><dt class="text-sm text-muted">Жильё</dt><dd>{{ $application->living_conditions }}</dd></div>
                    @endif
                    <div><dt class="text-sm text-muted">Получена</dt><dd>{{ LocalTime::dateTime($application->created_at) }}</dd></div>
                    @if ($application->processed_at)
                        <div><dt class="text-sm text-muted">Последнее решение</dt><dd>{{ LocalTime::dateTime($application->processed_at) }}</dd></div>
                    @endif
                </dl>

                @foreach (['experience' => 'О себе', 'message' => 'Комментарий'] as $field => $label)
                    @if ($application->{$field})
                        <div>
                            <h2 class="type-label">{{ $label }}</h2>
                            <p class="mt-1 whitespace-pre-line text-secondary">{{ $application->{$field} }}</p>
                        </div>
                    @endif
                @endforeach

                @if ($application->shelter_comment)
                    <x-alert tone="info" title="Комментарий приюта">{{ $application->shelter_comment }}</x-alert>
                @endif
            </x-card>

            <div class="grid content-start gap-6">
                <x-card padding="md" class="grid gap-3">
                    <p class="font-semibold">{{ $pet->name }}</p>
                    <p class="text-sm text-secondary">{{ $pet->summary() }}</p>
                    <x-badge :status="$pet->status" class="justify-self-start" />
                    <div class="flex flex-wrap gap-2">
                        <x-button variant="secondary" size="sm" icon="edit" :href="route('shelter.pets.edit', $pet)">Анкета</x-button>
                        @if (in_array($pet->status, \App\Enums\PetStatus::public(), true))
                            <x-button variant="ghost" size="sm" :href="route('pets.show', $pet)">На сайте</x-button>
                        @endif
                    </div>
                </x-card>

                @if ($next)
                    <x-card padding="md" class="grid gap-3">
                        <h2 class="font-semibold">Решение</h2>
                        @foreach ($next as $status)
                            @continue($status === ApplicationStatus::Rejected)
                            <form method="POST" action="{{ route('shelter.applications.update', $application) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $status->value }}">
                                <x-button :variant="$loop->first ? 'primary' : 'secondary'" block>{{ $actions[$status->value]['button'] }}</x-button>
                            </form>
                        @endforeach
                        @if (in_array(ApplicationStatus::Rejected, $next, true))
                            <x-button variant="ghost" type="button" data-modal-open="reject-application" block>Отклонить</x-button>
                        @endif
                        @if (in_array(ApplicationStatus::Completed, $next, true) && $application->type === \App\Enums\ApplicationType::Adoption)
                            <p class="text-xs text-muted">«Питомец передан» переведёт {{ $pet->name }} в статус «Нашёл дом» и закроет остальные заявки.</p>
                        @endif
                    </x-card>
                @endif
            </div>
        </div>
    </div>

    @if (in_array(ApplicationStatus::Rejected, $next, true))
        <x-modal id="reject-application" title="Отклонить заявку?">
            <form method="POST" action="{{ route('shelter.applications.update', $application) }}" id="reject-application-form" class="grid gap-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="rejected">
                <p>Человек увидит причину в своём кабинете.</p>
                <x-textarea name="shelter_comment" label="Причина" rows="3" maxlength="1000" required />
            </form>
            <x-slot:footer>
                <x-button variant="ghost" type="button" data-modal-close>Отмена</x-button>
                <x-button variant="danger" form="reject-application-form">Отклонить</x-button>
            </x-slot:footer>
        </x-modal>
    @endif
</x-layouts.cabinet>
