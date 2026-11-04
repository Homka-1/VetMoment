<?php

namespace App\Enums;

/**
 * Статус заявки на усыновление/опеку.
 */
enum ApplicationStatus: string
{
    use HasLabel;

    case New = 'new';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::InReview => 'На рассмотрении',
            self::Approved => 'Одобрена',
            self::Rejected => 'Отклонена',
            self::Withdrawn => 'Отозвана',
            self::Completed => 'Завершена',
        };
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::New => Tone::Info,
            self::InReview => Tone::Warning,
            self::Approved => Tone::Success,
            self::Rejected => Tone::Danger,
            self::Withdrawn => Tone::Neutral,
            self::Completed => Tone::Success,
        };
    }

    /**
     * Активные заявки — не более одной на (питомец, пользователь, тип).
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::New, self::InReview, self::Approved];
    }

    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }
}
