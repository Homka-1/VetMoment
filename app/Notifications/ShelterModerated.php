<?php

namespace App\Notifications;

use App\Enums\ShelterStatus;
use App\Enums\Tone;
use App\Models\Shelter;

/** Сотрудникам приюта: решение модерации (одобрен, отклонён, заблокирован, разблокирован). */
class ShelterModerated extends AppNotification
{
    private string $kind;

    public function __construct(Shelter $shelter, bool $unblocked = false)
    {
        $name = '«'.$shelter->name.'»';

        [$this->kind, $this->title, $this->body, $this->icon, $this->tone] = match (true) {
            $unblocked => [
                'shelter.unblocked', 'Приют снова на сайте',
                "Администратор снял блокировку с приюта {$name}: анкеты, сборы и выгулы снова видны всем.",
                'shield-check', Tone::Success,
            ],
            $shelter->status === ShelterStatus::Approved => [
                'shelter.approved', 'Приют прошёл проверку',
                "Приют {$name} проверен. Опубликуйте анкеты питомцев, запустите сбор и откройте запись на выгул.",
                'shield-check', Tone::Success,
            ],
            $shelter->status === ShelterStatus::Blocked => [
                'shelter.blocked', 'Приют заблокирован',
                "Администратор заблокировал приют {$name}: анкеты, сборы и выгулы скрыты с сайта.",
                'alert-triangle', Tone::Danger,
            ],
            default => [
                'shelter.rejected', 'Заявка приюта отклонена',
                "Приют {$name} не прошёл проверку. Исправьте заявку и отправьте её снова.",
                'alert-circle', Tone::Danger,
            ],
        };

        if (filled($shelter->moderation_comment)) {
            $this->body .= ' Причина: '.$shelter->moderation_comment;
        }

        [$this->url, $this->actionText] = $this->kind === 'shelter.rejected'
            ? [route('cabinet.shelter-request'), 'Исправить заявку']
            : [route('shelter.dashboard'), 'Открыть кабинет приюта'];

        parent::__construct();
    }

    public function kind(): string
    {
        return $this->kind;
    }
}
