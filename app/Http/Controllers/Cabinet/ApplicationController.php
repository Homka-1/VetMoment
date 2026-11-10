<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «Мои заявки» (/cabinet/applications): заявки «Забрать домой» и «Взять под опеку» и ответ приюта.
 * Отзыв заявки — прежний маршрут applications.withdraw.
 */
class ApplicationController extends Controller
{
    public const TABS = [
        'active' => 'Активные',
        'closed' => 'Закрытые',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? (string) $request->query('tab') : 'active';

        $active = ApplicationStatus::active();
        $base = fn () => $user->adoptionApplications();

        $counts = [
            'active' => $base()->whereIn('status', $active)->count(),
            'closed' => $base()->whereNotIn('status', $active)->count(),
        ];

        $applications = $base()
            ->when($tab === 'active', fn ($q) => $q->whereIn('status', $active), fn ($q) => $q->whereNotIn('status', $active))
            ->with(['pet.cover', 'pet.shelter'])
            ->latest()->latest('id')
            ->paginate(20)->withQueryString();

        return view('cabinet.applications', compact('applications', 'tab', 'counts'));
    }
}
