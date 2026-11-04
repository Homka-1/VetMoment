<?php

namespace App\Services;

use App\Enums\ShelterStatus;
use App\Models\Shelter;

/**
 * Решения администратора по приюту. Допустимость перехода проверяет ShelterPolicy
 * (approve / reject / block / unblock); уведомления о решении — этап 12.
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
    }

    public function reject(Shelter $shelter, string $reason): void
    {
        $shelter->update([
            'status' => ShelterStatus::Rejected,
            'moderation_comment' => $reason,
        ]);
    }

    /** Блокировка скрывает весь контент приюта (скоупы видимости требуют approved). */
    public function block(Shelter $shelter, string $reason): void
    {
        $shelter->update([
            'status' => ShelterStatus::Blocked,
            'moderation_comment' => $reason,
        ]);
    }

    public function unblock(Shelter $shelter): void
    {
        $shelter->update([
            'status' => ShelterStatus::Approved,
            'moderation_comment' => null,
        ]);
    }
}
