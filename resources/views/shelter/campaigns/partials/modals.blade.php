@use('App\Enums\CampaignStatus')
{{-- Подтверждения «Закрыть сбор» и «Перенести в архив» (список сборов и форма сбора). --}}
@if (in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Completed], true))
    <x-modal id="close-{{ $campaign->id }}" title="Закрыть сбор «{{ $campaign->title }}»?">
        Пожертвования перестанут приниматься, сбор останется на сайте как завершённый. Не забудьте опубликовать отчёт о расходах.
        <x-slot:footer>
            <x-button variant="ghost" type="button" data-modal-close>Отмена</x-button>
            <form method="POST" action="{{ route('shelter.campaigns.close', $campaign) }}">
                @csrf
                <x-button variant="danger" block>Закрыть сбор</x-button>
            </form>
        </x-slot:footer>
    </x-modal>
@endif
@if ($campaign->status !== CampaignStatus::Archived)
    <x-modal id="archive-{{ $campaign->id }}" title="Перенести «{{ $campaign->title }}» в архив?">
        Сбор пропадёт с сайта. Пожертвования и отчёты сохранятся — их видно в кабинете.
        <x-slot:footer>
            <x-button variant="ghost" type="button" data-modal-close>Отмена</x-button>
            <form method="POST" action="{{ route('shelter.campaigns.destroy', $campaign) }}">
                @csrf
                @method('DELETE')
                <x-button variant="danger" block>Перенести в архив</x-button>
            </form>
        </x-slot:footer>
    </x-modal>
@endif
