@props(['shelter'])
{{-- Баннер статуса модерации над разделами кабинета приюта (макет 10-pets): на проверке, отклонён, заблокирован.
     У одобренного приюта ничего не выводит. --}}
@if ($shelter->status === \App\Enums\ShelterStatus::Pending)
    <x-alert tone="warning" title="Приют на проверке" {{ $attributes }}>
        Пока модератор проверяет документы, анкеты сохраняются как черновики и не видны на сайте.
        Обычно проверка занимает 1–2 рабочих дня.
    </x-alert>
@elseif ($shelter->status === \App\Enums\ShelterStatus::Rejected)
    <x-alert tone="danger" title="Заявка приюта отклонена" {{ $attributes }}>
        <p>{{ $shelter->moderation_comment }}</p>
        <x-slot:action>
            <x-button variant="secondary" size="sm" :href="route('cabinet.shelter-request')">Исправить заявку</x-button>
        </x-slot:action>
    </x-alert>
@elseif ($shelter->status === \App\Enums\ShelterStatus::Blocked)
    <x-alert tone="danger" title="Приют заблокирован" {{ $attributes }}>
        Анкеты и сборы скрыты с сайта. {{ $shelter->moderation_comment }}
    </x-alert>
@endif
