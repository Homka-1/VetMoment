<?php

namespace App\Policies;

use App\Enums\ApplicationStatus;
use App\Models\AdoptionApplication;
use App\Models\User;

/**
 * Заявки «Хочу забрать» / «Взять под опеку» (docs/product/roles.md, 2.2).
 * Админ видит заявки, но обрабатывает их только приют.
 */
class AdoptionApplicationPolicy
{
    public function view(User $user, AdoptionApplication $application): bool
    {
        return $application->user_id === $user->id
            || $user->isAdmin()
            || $user->worksAt($application->pet->shelter_id);
    }

    /** Отозвать можно, пока приют не принял решение (new, in_review). */
    public function withdraw(User $user, AdoptionApplication $application): bool
    {
        return $application->user_id === $user->id
            && in_array($application->status, [ApplicationStatus::New, ApplicationStatus::InReview], true);
    }

    /** Смена статуса и комментарий приюта. */
    public function process(User $user, AdoptionApplication $application): bool
    {
        return $user->worksAt($application->pet->shelter_id);
    }
}
