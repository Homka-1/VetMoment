<?php

namespace App\Notifications;

use App\Enums\Tone;
use App\Models\WalkBooking;
use App\Notifications\Concerns\DescribesWalk;
use App\Support\Phone;

/** Волонтёру: напоминание о подтверждённой прогулке за сутки (команда app:send-walk-reminders). */
class WalkReminder extends AppNotification
{
    use DescribesWalk;

    public function __construct(WalkBooking $booking)
    {
        $booking->loadMissing('slot.shelter', 'pet');
        $slot = $booking->slot;

        $this->title = 'Скоро прогулка';
        $this->body = trim('Вас ждут в '.self::walkWhere($booking).': '.self::walkWhen($booking).'. '.self::walkPet($booking));
        $this->details = array_values(array_filter([
            $slot->meeting_point ? 'Место встречи: '.$slot->meeting_point.'.' : null,
            $slot->shelter->phone ? 'Телефон приюта: '.Phone::format($slot->shelter->phone).'.' : null,
            'Если не получается прийти — отмените запись, чтобы место досталось другому волонтёру.',
        ]));
        $this->url = route('walks.bookings.show', $booking);
        $this->actionText = 'Открыть запись';
        $this->icon = 'clock';
        $this->tone = Tone::Tag;

        parent::__construct();
    }

    public function kind(): string
    {
        return 'walk.reminder';
    }
}
