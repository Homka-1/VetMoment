<?php

namespace App\Enums;

/**
 * Роль пользователя. Одна на пользователя; права shelter_staff ⊇ volunteer.
 */
enum Role: string
{
    use HasLabel;

    case Volunteer = 'volunteer';
    case ShelterStaff = 'shelter_staff';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Volunteer => 'Волонтёр',
            self::ShelterStaff => 'Представитель приюта',
            self::Admin => 'Администратор',
        };
    }
}
