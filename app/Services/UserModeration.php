<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\CancelledBy;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Notifications\WalkBookingCancelled;
use App\Notifications\WalkBookingDecided;
use App\Support\Recipients;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Блокировка пользователей администратором. Заблокированному закрыт вход, а его будущие
 * активные записи на выгул отменяются (docs/product/roles.md) — места возвращаются в слоты;
 * волонтёру (письмом) и приютам сообщается об отмене.
 */
class UserModeration
{
    /** @return int сколько записей на выгул отменено */
    public function block(User $user): int
    {
        return DB::transaction(function () use ($user): int {
            $user->forceFill(['is_blocked' => true])->save();

            $bookings = WalkBooking::query()
                ->where('user_id', $user->getKey())
                ->active()
                ->whereHas('slot', fn (Builder $q) => $q->where('starts_at', '>', now()))
                ->with('slot.shelter', 'pet')
                ->get();

            foreach ($bookings as $booking) {
                $booking->update([
                    'status' => BookingStatus::Cancelled,
                    'cancelled_by' => CancelledBy::System,
                    'cancelled_at' => now(),
                    'shelter_comment' => 'Аккаунт волонтёра заблокирован.',
                ]);
            }

            WalkSlot::query()->whereIn('id', $bookings->pluck('walk_slot_id')->unique())->get()
                ->each(fn (WalkSlot $slot) => $slot->recalculateBookedCount());

            foreach ($bookings as $booking) {
                $booking->setRelation('user', $user);
                $user->notify(new WalkBookingDecided($booking));
                Notification::send(Recipients::shelterStaff($booking->slot->shelter), new WalkBookingCancelled($booking));
            }

            return $bookings->count();
        });
    }

    public function unblock(User $user): void
    {
        $user->forceFill(['is_blocked' => false])->save();
    }
}
