<?php

namespace App\Support;

use App\Models\Shelter;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Кому отправлять уведомления «для приюта»: все сотрудники приюта, кроме заблокированных.
 */
final class Recipients
{
    private function __construct() {}

    /**
     * @return Collection<int, User>
     */
    public static function shelterStaff(Shelter|int $shelter): Collection
    {
        $shelter = $shelter instanceof Shelter ? $shelter : Shelter::query()->findOrFail($shelter);

        return $shelter->members()->where('is_blocked', false)->get();
    }
}
