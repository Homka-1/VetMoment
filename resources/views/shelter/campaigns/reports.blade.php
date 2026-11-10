@use('App\Http\Requests\Shelter\CampaignReportRequest')
@use('App\Support\Money')
{{-- Отчёты о расходах сбора: форма нового отчёта (текст, сколько потрачено, фото и чеки) и опубликованные отчёты. --}}
@php
    $fileError = fn (string $name) => $errors->first($name) ?: collect($errors->get($name.'.*'))->flatten()->first();
    $inputClass = 'block w-full text-sm text-secondary file:mr-4 file:h-control file:cursor-pointer file:rounded-sm file:border-control file:border-ink-900 file:bg-white file:px-4 file:font-semibold file:text-primary hover:file:bg-ink-50';
@endphp
<x-layouts.cabinet :title="'Отчёты — '.$campaign->title" heading="Отчёты о расходах">
    <x-slot:actions>
        @if (in_array($campaign->status, \App\Enums\CampaignStatus::public(), true))
            <x-button variant="secondary" icon="tenge" :href="route('campaigns.show', $campaign)">Открыть сбор на сайте</x-button>
        @endif
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-6">
        <x-breadcrumbs :items="['Сборы' => route('shelter.campaigns.index'), $campaign->title => route('shelter.campaigns.edit', $campaign), 'Отчёты' => null]" />
        <x-form-errors />

        <x-card padding="lg" as="section" aria-labelledby="report-form-title">
            <h2 id="report-form-title" class="type-h4">Новый отчёт</h2>
            <p class="mt-1 text-sm text-secondary">
                Собрано {{ Money::format($campaign->raised_amount) }}. Отчёт сразу появится на странице сбора — расскажите, на что ушли деньги,
                и приложите фото покупок и чеков.
            </p>
            <form method="POST" action="{{ route('shelter.campaigns.reports.store', $campaign) }}" enctype="multipart/form-data" class="mt-6 grid gap-6" novalidate>
                @csrf
                <div class="grid gap-6 md:grid-cols-[2fr_1fr]">
                    <x-input name="title" label="Заголовок" required maxlength="150" placeholder="Первичный осмотр и рентген" />
                    <x-input name="spent" label="Потрачено" inputmode="numeric" suffix="₸" placeholder="32 500" autocomplete="off"
                             hint="Необязательно — иначе сложим строки расходов" />
                </div>
                <x-textarea name="body" label="Текст отчёта" required rows="6" maxlength="5000"
                            hint="Расходы пишите строками «— рентген, 2 проекции — 18 000 ₸» — на сайте они станут таблицей." />
                <div class="grid gap-6 md:grid-cols-2">
                    <x-form.field label="Фото покупок" for="f-photos" :error="$fileError('photos')" hint="JPG, PNG или WebP до 8 МБ">
                        <input type="file" name="photos[]" id="f-photos" multiple accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                    </x-form.field>
                    <x-form.field label="Фото чеков" for="f-receipts" :error="$fileError('receipts')" :hint="'Всего до '.CampaignReportRequest::MAX_FILES.' файлов в отчёте'">
                        <input type="file" name="receipts[]" id="f-receipts" multiple accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                    </x-form.field>
                </div>
                <div><x-button size="lg">Опубликовать отчёт</x-button></div>
            </form>
        </x-card>

        <section aria-labelledby="reports-list-title" class="grid gap-4">
            <h2 id="reports-list-title" class="type-h4">Опубликованные</h2>
            @forelse ($campaign->reports as $report)
                <x-campaign-report :report="$report">
                    <div class="mt-4 flex justify-end">
                        <x-button variant="ghost" size="sm" icon="trash" type="button" data-modal-open="delete-report-{{ $report->id }}">Удалить отчёт</x-button>
                    </div>
                </x-campaign-report>
                <x-modal id="delete-report-{{ $report->id }}" title="Удалить отчёт «{{ $report->title }}»?">
                    Отчёт и его фото пропадут со страницы сбора. Это нельзя отменить.
                    <x-slot:footer>
                        <x-button variant="ghost" type="button" data-modal-close>Отмена</x-button>
                        <form method="POST" action="{{ route('shelter.campaigns.reports.destroy', [$campaign, $report]) }}">
                            @csrf
                            @method('DELETE')
                            <x-button variant="danger" block>Удалить отчёт</x-button>
                        </form>
                    </x-slot:footer>
                </x-modal>
            @empty
                <p class="rounded-lg border border-dashed border-strong p-6 text-secondary">Отчётов пока нет.</p>
            @endforelse
        </section>
    </div>
</x-layouts.cabinet>
