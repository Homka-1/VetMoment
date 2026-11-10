<?php

namespace App\Http\Controllers;

use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\WalkSlot;
use App\Services\PlatformStats;
use Illuminate\View\View;

/**
 * Главная (макет 01): герой, три пути помощи и блоки с данными — питомцы, сборы, выгулы недели, приюты, цифры.
 */
class HomeController extends Controller
{
    public function __invoke(PlatformStats $stats): View
    {
        $weekEnds = now()->addDays(7);

        return view('home', [
            'pets' => Pet::query()->inCatalog()
                ->with(['cover', 'shelter'])
                ->orderByRaw('CASE WHEN arrived_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('arrived_at')->orderBy('id')
                ->limit(4)->get(),
            'campaigns' => DonationCampaign::query()->visible()->acceptingDonations()
                ->with(['shelter', 'pet.cover'])
                ->orderByRaw('CASE WHEN ends_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('ends_at')->orderBy('id')
                ->limit(3)->get(),
            'slots' => WalkSlot::query()->upcoming()
                ->where('starts_at', '<=', $weekEnds)
                ->whereHas('shelter', fn ($q) => $q->approved())
                ->with('shelter')
                ->limit(7)->get(),
            'shelters' => Shelter::query()->approved()
                ->withCount(['pets' => fn ($q) => $q->visible()])
                ->orderByDesc('pets_count')->orderBy('id')
                ->limit(4)->get(),
            'stats' => $stats->overall(),
        ]);
    }
}
