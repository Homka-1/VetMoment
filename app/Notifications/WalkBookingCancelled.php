<?php

namespace App\Notifications;

use App\Enums\CancelledBy;
use App\Enums\Tone;
use App\Models\WalkBooking;
use App\Notifications\Concerns\DescribesWalk;

/** Приюту: волонтёр отменил запись (или её отменила система) — место в слоте освободилось. */
class WalkBookingCancelled extends AppNotification
{
    use DescribesWalk;

    public function __construct(WalkBooking $booking)
    {
        $booking->loadMissing('slot.shelter', 'user');

        $this->title = 'Запись на выгул отменена';
        $this->body = $booking->cancelled_by === CancelledBy::User
            ? $booking->user->name.' отменяет прогулку '.self::walkWhen($booking).'. Место в слоте освободилось.'
            : 'Запись '.$booking->user->name.' на '.self::walkWhen($booking).' отменена автоматически. Место в слоте освободилось.';

        if ($booking->cancelled_by === CancelledBy::System && filled($booking->shelter_comment)) {
            $this->body .= ' Причина: '.$booking->shelter_comment;
        }

        $this->url = route('shelter.slots.show', $booking->walk_slot_id);
        $this->actionText = 'Открыть слот';
        $this->icon = 'leash';
        $this->tone = Tone::Neutral;

        parent::__construct();
    }

    public function kind(): string
    {
        return 'walk.cancelled_by_volunteer';
    }
}
