<?php

namespace App\Notifications;

use App\Enums\Tone;
use App\Models\DonationCampaign;
use App\Support\Money;

/** Сбор достиг цели: приюту — опубликовать отчёт, жертвователям с аккаунтом — новость. */
class CampaignGoalReached extends AppNotification
{
    public function __construct(DonationCampaign $campaign, bool $forShelter)
    {
        $goal = Money::format($campaign->goal_amount);

        $this->title = "Сбор «{$campaign->title}» собран";
        if ($forShelter) {
            $this->body = "Собрано {$goal} — цель достигнута. Расскажите жертвователям, на что ушли деньги: добавьте отчёт с чеками.";
            $this->url = route('shelter.campaigns.reports.index', $campaign);
            $this->actionText = 'Добавить отчёт';
        } else {
            $this->body = "Вместе собрали {$goal} — спасибо, что помогли. Отчёт приюта появится на странице сбора.";
            $this->url = route('campaigns.show', $campaign);
            $this->actionText = 'Открыть сбор';
        }
        $this->icon = 'check-circle';
        $this->tone = Tone::Success;

        parent::__construct();
    }

    public function kind(): string
    {
        return 'campaign.completed';
    }
}
