<?php

namespace App\Models;

use App\Enums\ShelterMemberRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Связь «сотрудник — приют» (shelter_user) с ролью внутри приюта.
 *
 * @property ShelterMemberRole $role
 */
class ShelterMember extends Pivot
{
    protected $table = 'shelter_user';

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => ShelterMemberRole::class,
        ];
    }
}
