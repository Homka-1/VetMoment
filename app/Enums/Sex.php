<?php

namespace App\Enums;

/**
 * Пол животного.
 */
enum Sex: string
{
    use HasLabel;

    case Male = 'male';
    case Female = 'female';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Мальчик',
            self::Female => 'Девочка',
            self::Unknown => 'Не известен',
        };
    }
}
