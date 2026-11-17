<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Enums\Tone;
use App\Models\AdoptionApplication;

/** Заявителю: приют взял заявку в работу, одобрил, отклонил или завершил её. */
class ApplicationDecided extends AppNotification
{
    private string $kind;

    public function __construct(AdoptionApplication $application)
    {
        $application->loadMissing('pet.shelter');
        $pet = $application->pet->name;
        $shelter = '«'.$application->pet->shelter->name.'»';

        [$this->title, $this->body, $this->icon, $this->tone] = match ($application->status) {
            ApplicationStatus::InReview => [
                "{$pet}: заявку рассматривают",
                "Приют {$shelter} взял заявку в работу и скоро позвонит.",
                'mail', Tone::Info,
            ],
            ApplicationStatus::Approved => [
                "{$pet}: заявка одобрена",
                "Приют {$shelter} одобрил заявку и свяжется с вами, чтобы договориться о встрече.",
                'check-circle', Tone::Success,
            ],
            ApplicationStatus::Completed => [
                "{$pet}: заявка завершена",
                "Приют {$shelter} отметил заявку завершённой. Спасибо, что помогли!",
                'heart', Tone::Meadow,
            ],
            default => [
                "{$pet}: заявка отклонена",
                "Приют {$shelter} отклонил заявку.",
                'x', Tone::Danger,
            ],
        };

        $this->kind = 'application.'.$application->status->value;

        if ($application->status === ApplicationStatus::Rejected && filled($application->shelter_comment)) {
            $this->body .= ' Причина: '.$application->shelter_comment;
        }

        $this->url = route('cabinet.applications', ['tab' => $application->status->isActive() ? 'active' : 'closed']);
        $this->actionText = 'Мои заявки';

        parent::__construct();
    }

    public function kind(): string
    {
        return $this->kind;
    }
}
