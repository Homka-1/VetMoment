<?php

namespace App\Enums;

/**
 * Статус записи на выгул.
 */
enum BookingStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ждёт подтверждения',
            self::Confirmed => 'Подтверждена',
            self::Rejected => 'Отклонена',
            self::Cancelled => 'Отменена',
            self::Completed => 'Состоялся',
            self::NoShow => 'Не пришёл',
        };
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Pending => Tone::Warning,
            self::Confirmed => Tone::Success,
            self::Rejected => Tone::Danger,
            self::Cancelled => Tone::Neutral,
            self::Completed => Tone::Meadow,
            self::NoShow => Tone::Danger,
        };
    }

    /**
     * Активные записи — занимают место в слоте (учитываются в walk_slots.booked_count).
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Pending, self::Confirmed];
    }

    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }
}
