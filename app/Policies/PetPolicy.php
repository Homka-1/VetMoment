<?php

namespace App\Policies;

use App\Enums\PetStatus;
use App\Models\Pet;
use App\Models\User;

/**
 * Анкеты питомцев (docs/product/roles.md, 2.2).
 * «Своё» — питомец приюта, где работает пользователь. Админ управляет любыми анкетами,
 * но не подаёт заявок «от имени» волонтёра.
 */
class PetPolicy
{
    /** Каталог открыт всем, включая гостей. */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /** Опубликованная анкета — всем; черновик, архив, скрытая — только своему приюту и админу. */
    public function view(?User $user, Pet $pet): bool
    {
        if ($this->isPublic($pet)) {
            return true;
        }

        return $user !== null && $this->manages($user, $pet);
    }

    /** Черновики можно создавать и до одобрения приюта. */
    public function create(User $user): bool
    {
        return $user->isAdmin() || ($user->isShelterStaff() && $user->shelter() !== null);
    }

    /** Редактирование, фото, смена статуса. */
    public function update(User $user, Pet $pet): bool
    {
        return $this->manages($user, $pet);
    }

    /** Публикация (выход из черновика) — только у одобренного приюта (📝). */
    public function publish(User $user, Pet $pet): bool
    {
        return $this->manages($user, $pet) && $pet->shelter->isApproved();
    }

    /** «Удаление» анкеты = перевод в архив. */
    public function delete(User $user, Pet $pet): bool
    {
        return $this->manages($user, $pet);
    }

    /** Скрыть / вернуть анкету — модерация. */
    public function hide(User $user, Pet $pet): bool
    {
        return $user->isAdmin();
    }

    /** Заявки на питомца и контакты заявителей. */
    public function viewApplications(User $user, Pet $pet): bool
    {
        return $this->manages($user, $pet);
    }

    /** «Хочу забрать» / «Взять под опеку»: не админ, не свой приют, питомец ещё ищет дом. */
    public function apply(User $user, Pet $pet): bool
    {
        return ! $user->isAdmin()
            && ! $user->worksAt($pet->shelter_id)
            && $this->isPublic($pet)
            && in_array($pet->status, PetStatus::catalog(), true);
    }

    public function favorite(User $user, Pet $pet): bool
    {
        return $this->isPublic($pet);
    }

    private function manages(User $user, Pet $pet): bool
    {
        return $user->isAdmin() || $user->worksAt($pet->shelter_id);
    }

    private function isPublic(Pet $pet): bool
    {
        return ! $pet->is_hidden
            && in_array($pet->status, PetStatus::public(), true)
            && $pet->shelter->isApproved();
    }
}
