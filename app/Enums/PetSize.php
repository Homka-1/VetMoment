<?php

namespace App\Enums;

/**
 * Размер питомца.
 */
enum PetSize: string
{
    use HasLabel;

    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';

    public function label(): string
    {
        return match ($this) {
            self::Small => 'Маленький',
            self::Medium => 'Средний',
            self::Large => 'Крупный',
        };
    }
}
