<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CampaignReport;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\WalkSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Публичные страницы приютов: список (/shelters) и страница приюта (макет 07).
 */
class ShelterController extends Controller
{
    public function index(Request $request): View
    {
        $city = trim((string) $request->query('city'));

        $cities = Shelter::query()->approved()->orderBy('city')->distinct()->pluck('city');

        $shelters = Shelter::query()->approved()
            ->when($city !== '' && $cities->contains($city), fn ($q) => $q->where('city', $city))
            ->withCount(['pets' => fn ($q) => $q->visible(), 'campaigns' => fn ($q) => $q->visible()->acceptingDonations()])
            ->orderBy('name')
            ->paginate(12)->withQueryString();

        return view('shelters.index', [
            'shelters' => $shelters,
            'cities' => $cities,
            'city' => $cities->contains($city) ? $city : '',
        ]);
    }

    public function show(Request $request, Shelter $shelter): View
    {
        abort_unless(Gate::forUser($request->user())->allows('view', $shelter), 404);

        $pets = Pet::query()->inCatalog()->where('shelter_id', $shelter->id);

        return view('shelters.show', [
            'shelter' => $shelter,
            'petsCount' => (clone $pets)->count(),
            'pets' => (clone $pets)->with(['cover', 'shelter'])->orderBy('arrived_at')->orderBy('id')->limit(4)->get(),
            'campaigns' => DonationCampaign::query()->visible()->where('shelter_id', $shelter->id)
                ->with(['shelter', 'pet.cover'])->latest()->limit(2)->get(),
            'reports' => CampaignReport::query()
                ->whereHas('campaign', fn ($q) => $q->visible()->where('shelter_id', $shelter->id))
                ->whereNotNull('published_at')
                ->with(['attachments', 'campaign'])->latest('published_at')->limit(2)->get(),
            'slots' => WalkSlot::query()->upcoming()->where('shelter_id', $shelter->id)
                ->where('starts_at', '<=', WalkSlot::horizonEndsAt())->limit(4)->get(),
        ]);
    }
}
