<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WalkTemplate;

/**
 * Шаблоны недели: управляет приют-владелец слотов и админ (как WalkSlotPolicy::update).
 */
class WalkTemplatePolicy
{
    public function delete(User $user, WalkTemplate $template): bool
    {
        return $user->isAdmin() || $user->worksAt($template->shelter_id);
    }
}
