<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WalkBooking;

/**
 * Записи на выгул (docs/product/roles.md, 2.4).
 * Срок отмены (не позже чем за N ч) и прочие правила — в сервисе этапа 10.
 */
class WalkBookingPolicy
{
    public function view(User $user, WalkBooking $booking): bool
    {
        return $booking->user_id === $user->id || $this->manages($user, $booking);
    }

    /** Волонтёр отменяет только свою активную запись. */
    public function cancel(User $user, WalkBooking $booking): bool
    {
        return $booking->user_id === $user->id && $booking->status->isActive();
    }

    /** Подтвердить / отклонить / «состоялся» / «не пришёл», видеть телефон волонтёра. */
    public function manage(User $user, WalkBooking $booking): bool
    {
        return $this->manages($user, $booking);
    }

    private function manages(User $user, WalkBooking $booking): bool
    {
        return $user->isAdmin() || $user->worksAt($booking->slot->shelter_id);
    }
}
