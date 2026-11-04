<?php

namespace Tests\Concerns;

use App\Enums\ShelterMemberRole;
use App\Models\Shelter;
use App\Models\User;

/**
 * Участники сценариев: волонтёр, админ, сотрудник конкретного приюта.
 */
trait CreatesActors
{
    protected function volunteer(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function staffOf(Shelter $shelter, ShelterMemberRole $role = ShelterMemberRole::Owner): User
    {
        $user = User::factory()->shelterStaff()->create();
        $shelter->members()->attach($user, ['role' => $role]);

        return $user->fresh();
    }
}
