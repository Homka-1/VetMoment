<?php

namespace App\Notifications;

use App\Enums\Tone;
use App\Models\WalkBooking;
use App\Notifications\Concerns\DescribesWalk;

/** Приюту: волонтёр записался на выгул, запись ждёт подтверждения. */
class WalkBookingCreated extends AppNotification
{
    use DescribesWalk;

    public function __construct(WalkBooking $booking)
    {
        $booking->loadMissing('slot.shelter', 'pet', 'user');

        $this->title = 'Новая запись на выгул';
        $this->body = trim($booking->user->name.' — '.self::walkWhen($booking).'. '.self::walkPet($booking).' Подтвердите или отклоните запись.');
        $this->url = route('shelter.bookings.index', ['tab' => 'pending']);
        $this->actionText = 'Открыть записи';
        $this->icon = 'leash';
        $this->tone = Tone::Warning;

        if (filled($booking->comment)) {
            $this->details[] = 'Комментарий волонтёра: '.$booking->comment;
        }

        parent::__construct();
    }

    public function kind(): string
    {
        return 'walk.booked';
    }
}
