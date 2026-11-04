<?php

namespace App\Enums;

/**
 * Кто отменил запись на выгул.
 */
enum CancelledBy: string
{
    use HasLabel;

    case User = 'user';
    case Shelter = 'shelter';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Волонтёр',
            self::Shelter => 'Приют',
            self::System => 'Система',
        };
    }
}
