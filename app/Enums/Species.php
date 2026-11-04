<?php

namespace App\Enums;

/**
 * Вид животного.
 */
enum Species: string
{
    use HasLabel;

    case Cat = 'cat';
    case Dog = 'dog';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cat => 'Кошка',
            self::Dog => 'Собака',
            self::Other => 'Другое',
        };
    }
}
