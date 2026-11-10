<?php

namespace App\Enums;

/**
 * Статус платежа (пожертвования).
 */
enum PaymentStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает оплаты',
            self::Succeeded => 'Оплачено',
            self::Failed => 'Ошибка оплаты',
            self::Expired => 'Срок оплаты истёк',
            self::Refunded => 'Возврат',
        };
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Pending => Tone::Warning,
            self::Succeeded => Tone::Success,
            self::Failed => Tone::Danger,
            self::Expired => Tone::Neutral,
            self::Refunded => Tone::Info,
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }
}
