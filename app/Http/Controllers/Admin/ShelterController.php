<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShelterStatus;
use App\Http\Controllers\Controller;
use App\Models\Shelter;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Очередь модерации приютов (макет 11-shelters). Решения — ShelterModerationController.
 */
class ShelterController extends Controller
{
    public const TABS = [
        'pending' => ['label' => 'На проверке', 'status' => ShelterStatus::Pending],
        'approved' => ['label' => 'Проверенные', 'status' => ShelterStatus::Approved],
        'rejected' => ['label' => 'Отклонённые', 'status' => ShelterStatus::Rejected],
        'blocked' => ['label' => 'Заблокированные', 'status' => ShelterStatus::Blocked],
    ];

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('status'), self::TABS) ? (string) $request->query('status') : 'pending';

        $counts = collect(self::TABS)->map(fn (array $t) => Shelter::query()->where('status', $t['status'])->count())->all();

        $shelters = Shelter::query()
            ->where('status', self::TABS[$tab]['status'])
            ->with(['owners'])
            ->withCount('pets')
            ->latest()->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.shelters', compact('shelters', 'tab', 'counts'));
    }
}
