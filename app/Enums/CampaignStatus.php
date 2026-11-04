<?php

namespace App\Enums;

/**
 * Статус сбора пожертвований.
 */
enum CampaignStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Active => 'Идёт сбор',
            self::Completed => 'Цель достигнута',
            self::Closed => 'Завершён',
            self::Archived => 'В архиве',
        };
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Draft => Tone::Neutral,
            self::Active => Tone::Tag,
            self::Completed => Tone::Success,
            self::Closed => Tone::Neutral,
            self::Archived => Tone::Neutral,
        };
    }

    /**
     * Статусы, видимые публично (при is_hidden = false).
     *
     * @return list<self>
     */
    public static function public(): array
    {
        return [self::Active, self::Completed, self::Closed];
    }
}
