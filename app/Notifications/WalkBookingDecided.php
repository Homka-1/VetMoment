<?php

namespace App\Notifications;

use App\Enums\BookingStatus;
use App\Enums\CancelledBy;
use App\Enums\Tone;
use App\Models\WalkBooking;
use App\Notifications\Concerns\DescribesWalk;

/**
 * Волонтёру: приют подтвердил или отклонил запись, отменил прогулку (в т.ч. слот целиком),
 * либо запись отменила система (блокировка аккаунта, приют не подтвердил до начала).
 */
class WalkBookingDecided extends AppNotification
{
    use DescribesWalk;

    private string $kind;

    public function __construct(WalkBooking $booking)
    {
        $booking->loadMissing('slot.shelter', 'pet');
        $when = self::walkWhen($booking);
        $where = self::walkWhere($booking);

        [$this->kind, $this->title, $this->body, $this->icon, $this->tone] = match (true) {
            $booking->status === BookingStatus::Confirmed => [
                'walk.confirmed', 'Прогулка подтверждена',
                trim("Приют {$where} ждёт вас: {$when}. ".self::walkPet($booking)),
                'check-circle', Tone::Success,
            ],
            $booking->status === BookingStatus::Rejected => [
                'walk.rejected', 'Приют отклонил запись на выгул',
                "Запись на {$when} в {$where} отклонена. Выберите другое время — свободные места есть в расписании.",
                'x', Tone::Danger,
            ],
            $booking->cancelled_by === CancelledBy::System => [
                'walk.cancelled', 'Запись на выгул отменена',
                "Запись на {$when} в {$where} отменена автоматически.",
                'x', Tone::Neutral,
            ],
            default => [
                'walk.cancelled', 'Приют отменил прогулку',
                "Прогулка {$when} в {$where} не состоится. Выберите другое время — свободные места есть в расписании.",
                'x', Tone::Neutral,
            ],
        };

        if (filled($booking->shelter_comment)) {
            $this->details[] = 'Причина: '.$booking->shelter_comment;
            $this->body .= ' Причина: '.$booking->shelter_comment;
        }

        $this->url = route('walks.bookings.show', $booking);
        $this->actionText = 'Открыть запись';

        parent::__construct();
    }

    public function kind(): string
    {
        return $this->kind;
    }
}
