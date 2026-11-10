<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\DonationCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Модерация сборов: список и «Скрыть» / «Вернуть» (CampaignPolicy::hide).
 */
class CampaignController extends Controller
{
    public const TABS = [
        'all' => 'Все',
        'hidden' => 'Скрытые',
    ];

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? (string) $request->query('tab') : 'all';

        $base = fn () => DonationCampaign::query()->where('status', '!=', CampaignStatus::Draft);

        $campaigns = $base()
            ->when($tab === 'hidden', fn ($q) => $q->where('is_hidden', true))
            ->with('shelter')
            ->latest('updated_at')->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.campaigns', [
            'campaigns' => $campaigns,
            'tab' => $tab,
            'counts' => ['all' => $base()->count(), 'hidden' => $base()->where('is_hidden', true)->count()],
        ]);
    }

    public function hide(DonationCampaign $campaign): RedirectResponse
    {
        Gate::authorize('hide', $campaign);
        $campaign->update(['is_hidden' => true]);

        return back()->with('status', "Сбор «{$campaign->title}» скрыт с сайта");
    }

    public function restore(DonationCampaign $campaign): RedirectResponse
    {
        Gate::authorize('hide', $campaign);
        $campaign->update(['is_hidden' => false]);

        return back()->with('status', "Сбор «{$campaign->title}» возвращён на сайт");
    }
}
