<?php

namespace App\Enums;

/**
 * Статус модерации приюта.
 */
enum ShelterStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'На модерации',
            self::Approved => 'Проверенный приют',
            self::Rejected => 'Отклонён',
            self::Blocked => 'Заблокирован',
        };
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Pending => Tone::Warning,
            self::Approved => Tone::Success,
            self::Rejected => Tone::Danger,
            self::Blocked => Tone::Danger,
        };
    }

    /** Заблокированный приют рисуется сплошным бейджем. */
    public function isSolid(): bool
    {
        return $this === self::Blocked;
    }
}
