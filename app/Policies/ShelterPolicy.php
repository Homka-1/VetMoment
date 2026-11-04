<?php

namespace App\Policies;

use App\Enums\Role;
use App\Enums\ShelterStatus;
use App\Models\Shelter;
use App\Models\User;

/**
 * Приюты: публичные страницы, заявка на регистрацию, профиль и сотрудники, модерация
 * (docs/product/roles.md, 2.1 и 2.5; flow — docs/product/user-flows.md, 6).
 */
class ShelterPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /** Публично — только одобренные; свои сотрудники и админ видят приют в любом статусе. */
    public function view(?User $user, Shelter $shelter): bool
    {
        if ($shelter->isApproved()) {
            return true;
        }

        return $user !== null && ($user->isAdmin() || $user->worksAt($shelter));
    }

    /** «Стать приютом-партнёром»: только волонтёр без приюта. */
    public function register(User $user): bool
    {
        return $user->role === Role::Volunteer && $user->shelter() === null;
    }

    /** Исправить отклонённую заявку и отправить снова (rejected → pending). */
    public function resubmit(User $user, Shelter $shelter): bool
    {
        return $shelter->status === ShelterStatus::Rejected && $user->ownsShelter($shelter);
    }

    /** Профиль приюта (описание, контакты, логотип, правила выгула). */
    public function update(User $user, Shelter $shelter): bool
    {
        return $user->isAdmin() || $user->ownsShelter($shelter);
    }

    /** Приглашение и удаление сотрудников. */
    public function manageStaff(User $user, Shelter $shelter): bool
    {
        return $user->isAdmin() || $user->ownsShelter($shelter);
    }

    /** Кабинет приюта: сводка, пожертвования, экспорт CSV. */
    public function manage(User $user, Shelter $shelter): bool
    {
        return $user->isAdmin() || $user->worksAt($shelter);
    }

    /** Пожертвование в общий фонд — только одобренному приюту. */
    public function donate(?User $user, Shelter $shelter): bool
    {
        return $shelter->isApproved();
    }

    // --- Модерация (только админ; переходы — по диаграмме статусов) ---

    public function approve(User $user, Shelter $shelter): bool
    {
        return $user->isAdmin() && $shelter->status === ShelterStatus::Pending;
    }

    public function reject(User $user, Shelter $shelter): bool
    {
        return $user->isAdmin() && $shelter->status === ShelterStatus::Pending;
    }

    public function block(User $user, Shelter $shelter): bool
    {
        return $user->isAdmin() && $shelter->status === ShelterStatus::Approved;
    }

    public function unblock(User $user, Shelter $shelter): bool
    {
        return $user->isAdmin() && $shelter->status === ShelterStatus::Blocked;
    }
}
