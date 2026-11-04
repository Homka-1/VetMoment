<?php

namespace App\Policies;

use App\Enums\WalkSlotStatus;
use App\Models\User;
use App\Models\WalkSlot;

/**
 * Слоты выгула (docs/product/roles.md, 2.4).
 * Здесь — только «кто»; правила бронирования W1–W10 (места, время, лимиты) проверяет сервис этапа 10.
 */
class WalkSlotPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, WalkSlot $slot): bool
    {
        if ($slot->shelter->isApproved()) {
            return true;
        }

        return $user !== null && $this->manages($user, $slot);
    }

    /** У слотов нет черновиков, поэтому создавать их может только одобренный приют (📝). */
    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isShelterStaff() && (bool) $user->shelter()?->isApproved();
    }

    /** Изменение и отмена слота. */
    public function update(User $user, WalkSlot $slot): bool
    {
        return $this->manages($user, $slot);
    }

    public function delete(User $user, WalkSlot $slot): bool
    {
        return $this->manages($user, $slot);
    }

    /** Список записавшихся с телефонами. */
    public function viewBookings(User $user, WalkSlot $slot): bool
    {
        return $this->manages($user, $slot);
    }

    /** Записаться: не админ, не свой приют, приют одобрен, слот не отменён. */
    public function book(User $user, WalkSlot $slot): bool
    {
        return ! $user->isAdmin()
            && ! $user->worksAt($slot->shelter_id)
            && $slot->shelter->isApproved()
            && $slot->status === WalkSlotStatus::Open;
    }

    private function manages(User $user, WalkSlot $slot): bool
    {
        return $user->isAdmin() || $user->worksAt($slot->shelter_id);
    }
}
