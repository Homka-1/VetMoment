@use('App\Enums\CampaignStatus')
@use('App\Support\Money')
@use('Illuminate\Support\Facades\Storage')
{{-- Сбор в кабинете приюта: о сборе, деньги и срок, обложка. Создание — «Запустить сбор» / «Сохранить черновик»;
     у черновика — «Запустить сбор», у идущего — «Закрыть сбор». Собранная сумма здесь не редактируется. --}}
@php
    $editing = $campaign->exists;
    $title = $editing ? $campaign->title : 'Новый сбор';
    $approved = $shelter->isApproved();
    $isDraft = $campaign->status === CampaignStatus::Draft;
    $endsAt = $campaign->ends_at ? \App\Support\LocalTime::of($campaign->ends_at)->format('Y-m-d') : null;
    $goal = $campaign->goal_amount ? number_format(intdiv($campaign->goal_amount, 100), 0, ',', ' ') : null;
    $maxEnd = ($campaign->starts_at ?? now())->copy()->addYear()->format('Y-m-d');
@endphp
<x-layouts.cabinet :title="$title" :heading="$editing ? 'Сбор: '.$campaign->title : 'Новый сбор'">
    <x-slot:actions>
        @if ($editing && in_array($campaign->status, CampaignStatus::public(), true))
            <x-button variant="secondary" icon="tenge" :href="route('campaigns.show', $campaign)">Открыть на сайте</x-button>
        @endif
        @if ($editing)
            <x-button variant="secondary" icon="image" :href="route('shelter.campaigns.reports.index', $campaign)">Отчёты</x-button>
        @endif
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-6">
        <x-breadcrumbs :items="['Сборы' => route('shelter.campaigns.index'), $title => null]" />
        <x-shelter-moderation :shelter="$shelter" />
        <x-form-errors />

        @if ($editing)
            <x-card class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                <div class="grid gap-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-badge :status="$campaign->status" />
                        <span class="text-sm text-secondary">Собрано {{ Money::format($campaign->raised_amount) }} из {{ Money::format($campaign->goal_amount) }}</span>
                    </div>
                    <x-progress :raised="$campaign->raised_amount" :goal="$campaign->goal_amount" compact class="max-w-md" />
                </div>
                <x-button variant="ghost" size="sm" :href="route('shelter.donations.index', ['campaign' => $campaign->id])">Пожертвования</x-button>
            </x-card>
        @endif

        <form method="POST" action="{{ $editing ? route('shelter.campaigns.update', $campaign) : route('shelter.campaigns.store') }}"
              enctype="multipart/form-data" class="grid gap-6" novalidate>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <x-card padding="lg" as="section" aria-labelledby="campaign-form-about" class="grid gap-6">
                <h2 id="campaign-form-about" class="type-h4">О сборе</h2>
                <x-input name="title" label="Название" :value="$campaign->title" required maxlength="120"
                         hint="Что и для кого: «Операция на лапе для Ромашки»" />
                <x-input name="short_description" label="Коротко" :value="$campaign->short_description" required maxlength="200"
                         hint="Одна фраза для карточки и ссылки в соцсетях, до 200 символов" />
                <x-textarea name="description" label="Описание" :value="$campaign->description" required rows="10" maxlength="10000"
                            hint="Что случилось и на что нужны деньги. Смету пишите строками «— операция и наркоз — 380 000 ₸» — на сайте она станет таблицей." />
                <x-select name="pet_id" label="Питомец" :options="$pets" :value="$campaign->pet_id" placeholder="Не на конкретного питомца" class="md:max-w-sm"
                          hint="Сбор появится на карточке питомца" />
            </x-card>

            <x-card padding="lg" as="section" aria-labelledby="campaign-form-money" class="grid gap-6">
                <h2 id="campaign-form-money" class="type-h4">Цель и срок</h2>
                <div class="grid gap-6 md:grid-cols-2">
                    <x-input name="goal" label="Цель" :value="$goal" required inputmode="numeric" suffix="₸" placeholder="300 000"
                             hint="От 5 000 до 50 000 000 ₸" autocomplete="off" />
                    <x-input name="ends_at" type="date" label="Дата окончания" :value="$endsAt" :min="now()->addDay()->format('Y-m-d')" :max="$maxEnd"
                             hint="Не дольше года. Пусто — до достижения цели" />
                </div>
                <x-toggle name="allow_overfunding" :checked="$campaign->allow_overfunding">Принимать пожертвования сверх цели до даты окончания</x-toggle>
            </x-card>

            <x-card padding="lg" as="section" aria-labelledby="campaign-form-cover" class="grid gap-5">
                <div>
                    <h2 id="campaign-form-cover" class="type-h4">Обложка</h2>
                    <p class="mt-1 text-sm text-secondary">JPG, PNG или WebP до 8 МБ. Без обложки покажем фото питомца или иллюстрацию.</p>
                </div>
                @if ($campaign->cover_path)
                    <div class="flex flex-wrap items-end gap-4">
                        <img src="{{ Storage::disk('public')->url($campaign->cover_path) }}" alt="Текущая обложка" class="h-32 w-auto rounded-md border border-subtle object-cover">
                        <x-checkbox name="remove_cover">Убрать обложку</x-checkbox>
                    </div>
                @endif
                <x-form.field :label="$campaign->cover_path ? 'Заменить обложку' : 'Файл обложки'" for="f-cover" :error="$errors->first('cover')">
                    <input type="file" name="cover" id="f-cover" accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-secondary file:mr-4 file:h-control file:cursor-pointer file:rounded-sm file:border-control file:border-ink-900 file:bg-white file:px-4 file:font-semibold file:text-primary hover:file:bg-ink-50"
                           @if ($errors->has('cover')) aria-invalid="true" aria-describedby="f-cover-error" @endif>
                </x-form.field>
            </x-card>

            <div class="flex flex-wrap items-center gap-3">
                @if (! $editing || $isDraft)
                    <x-button size="lg" name="action" value="publish" :disabled="! $approved">Запустить сбор</x-button>
                    <x-button size="lg" variant="secondary" name="action" value="{{ $editing ? 'save' : 'draft' }}">Сохранить черновик</x-button>
                @else
                    <x-button size="lg" name="action" value="save">Сохранить</x-button>
                @endif
                <x-button size="lg" variant="ghost" :href="route('shelter.campaigns.index')">Отмена</x-button>
            </div>
            @if ((! $editing || $isDraft) && ! $approved)
                <p class="-mt-3 text-sm text-secondary">Пока приют на проверке, сбор можно сохранить только как черновик.</p>
            @endif
        </form>

        @if ($editing && $campaign->status !== CampaignStatus::Archived)
            <x-card padding="lg" as="section" aria-labelledby="campaign-form-finish" class="grid gap-4">
                <h2 id="campaign-form-finish" class="type-h4">Завершение</h2>
                <p class="text-sm text-secondary">
                    Закрытый сбор остаётся на сайте с отчётами, но не принимает деньги. Архив убирает сбор с сайта; пожертвования сохраняются.
                </p>
                <div class="flex flex-wrap gap-3">
                    @if (in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Completed], true))
                        <x-button variant="secondary" type="button" data-modal-open="close-{{ $campaign->id }}">Закрыть сбор</x-button>
                    @endif
                    <x-button variant="ghost" icon="trash" type="button" data-modal-open="archive-{{ $campaign->id }}">Перенести в архив</x-button>
                </div>
            </x-card>
            @include('shelter.campaigns.partials.modals', ['campaign' => $campaign])
        @endif
    </div>
</x-layouts.cabinet>
