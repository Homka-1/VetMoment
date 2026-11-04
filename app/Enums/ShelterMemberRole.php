<?php

namespace App\Enums;

/**
 * Роль сотрудника внутри приюта (shelter_user.role).
 */
enum ShelterMemberRole: string
{
    use HasLabel;

    case Owner = 'owner';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Владелец',
            self::Staff => 'Сотрудник',
        };
    }
}
