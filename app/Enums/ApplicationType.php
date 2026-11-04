<?php

namespace App\Enums;

/**
 * Тип заявки на питомца.
 */
enum ApplicationType: string
{
    use HasLabel;

    case Adoption = 'adoption';
    case Guardianship = 'guardianship';

    public function label(): string
    {
        return match ($this) {
            self::Adoption => 'Забрать домой',
            self::Guardianship => 'Взять под опеку',
        };
    }
}
