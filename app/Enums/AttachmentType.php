<?php

namespace App\Enums;

/**
 * Тип вложения отчёта о расходах.
 */
enum AttachmentType: string
{
    use HasLabel;

    case Photo = 'photo';
    case Receipt = 'receipt';

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Фото',
            self::Receipt => 'Чек',
        };
    }
}
