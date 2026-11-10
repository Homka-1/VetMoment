<?php

namespace App\Policies;

use App\Models\User;

/**
 * Управление пользователями в админке: смотреть список и блокировать.
 * Администратор не блокирует себя и других администраторов.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function block(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $target->isAdmin() && ! $target->is_blocked;
    }

    public function unblock(User $user, User $target): bool
    {
        return $user->isAdmin() && $target->is_blocked;
    }
}
