<?php

namespace App\Enums;

/**
 * Хранимый статус слота выгула. «Заполнен» и «прошёл» — вычисляемые состояния (WalkSlot::state()).
 */
enum WalkSlotStatus: string
{
    use HasLabel;

    case Open = 'open';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Открыт',
            self::Cancelled => 'Отменён',
        };
    }
}
