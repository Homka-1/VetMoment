<?php

namespace App\Services;

use App\Enums\ShelterStatus;
use App\Models\Shelter;
use App\Notifications\ShelterModerated;
use App\Support\Recipients;
use Illuminate\Support\Facades\Notification;

/**
 * Решения администратора по приюту. Допустимость перехода проверяет ShelterPolicy
 * (approve / reject / block / unblock). О решении сообщается сотрудникам приюта.
 */
class ShelterModeration
{
    public function approve(Shelter $shelter): void
    {
        $shelter->update([
            'status' => ShelterStatus::Approved,
            'moderation_comment' => null,
            'approved_at' => now(),
        ]);

        $this->notify($shelter);
    }

    public function reject(Shelter $shelter, string $reason): void
    {
        $shelter->update([
            'status' => ShelterStatus::Rejected,
            'moderation_comment' => $reason,
        ]);

        $this->notify($shelter);
    }

    /** Блокировка скрывает весь контент приюта (скоупы видимости требуют approved). */
    public function block(Shelter $shelter, string $reason): void
    {
        $shelter->update([
            'status' => ShelterStatus::Blocked,
            'moderation_comment' => $reason,
        ]);

        $this->notify($shelter);
    }

    public function unblock(Shelter $shelter): void
    {
        $shelter->update([
            'status' => ShelterStatus::Approved,
            'moderation_comment' => null,
        ]);

        $this->notify($shelter, unblocked: true);
    }

    private function notify(Shelter $shelter, bool $unblocked = false): void
    {
        Notification::send(Recipients::shelterStaff($shelter), new ShelterModerated($shelter, $unblocked));
    }
}
