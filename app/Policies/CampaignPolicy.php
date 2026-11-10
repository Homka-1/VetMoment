<?php

namespace App\Policies;

use App\Enums\CampaignStatus;
use App\Models\DonationCampaign;
use App\Models\User;

/**
 * Сборы пожертвований (docs/product/roles.md, 2.3).
 */
class CampaignPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, DonationCampaign $campaign): bool
    {
        if ($this->isPublic($campaign)) {
            return true;
        }

        return $user !== null && $this->manages($user, $campaign);
    }

    /** Черновик сбора можно готовить и до одобрения приюта. */
    public function create(User $user): bool
    {
        return $user->isAdmin() || ($user->isShelterStaff() && $user->shelter() !== null);
    }

    /** Редактирование и закрытие сбора. */
    public function update(User $user, DonationCampaign $campaign): bool
    {
        return $this->manages($user, $campaign);
    }

    /** Запуск сбора (draft → active) — только у одобренного приюта (📝). */
    public function publish(User $user, DonationCampaign $campaign): bool
    {
        return $this->manages($user, $campaign) && $campaign->shelter->isApproved();
    }

    /** «Удаление» = архив; история пожертвований сохраняется. */
    public function delete(User $user, DonationCampaign $campaign): bool
    {
        return $this->manages($user, $campaign);
    }

    public function hide(User $user, DonationCampaign $campaign): bool
    {
        return $user->isAdmin();
    }

    /** Публикация отчётов о расходах. */
    public function manageReports(User $user, DonationCampaign $campaign): bool
    {
        return $this->manages($user, $campaign);
    }

    /** Полный список пожертвований (email, статус платежа) и экспорт CSV. */
    public function viewDonations(User $user, DonationCampaign $campaign): bool
    {
        return $this->manages($user, $campaign);
    }

    /**
     * Пожертвовать может любой, в т.ч. гость, — пока сбор виден публично и принимает деньги:
     * идёт и срок не истёк, либо цель достигнута, но разрешено собирать сверх цели.
     */
    public function donate(?User $user, DonationCampaign $campaign): bool
    {
        return $this->isPublic($campaign) && $campaign->acceptsDonations();
    }

    private function manages(User $user, DonationCampaign $campaign): bool
    {
        return $user->isAdmin() || $user->worksAt($campaign->shelter_id);
    }

    private function isPublic(DonationCampaign $campaign): bool
    {
        return ! $campaign->is_hidden
            && in_array($campaign->status, CampaignStatus::public(), true)
            && $campaign->shelter->isApproved();
    }
}
