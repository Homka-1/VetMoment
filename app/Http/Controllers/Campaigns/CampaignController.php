<?php

namespace App\Http\Controllers\Campaigns;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\DonationCampaign;
use App\Models\Shelter;
use App\Support\DonationPrefill;
use App\Support\Estimate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Публичные страницы сборов: список /campaigns (макет 04), страница сбора (макет 05), все пожертвования сбора.
 */
class CampaignController extends Controller
{
    private const PER_PAGE = 12;

    private const RECENT_DONATIONS = 5;

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'finished' ? 'finished' : 'active';
        $shelterSlug = is_string($request->query('shelter')) ? $request->query('shelter') : null;
        $petOnly = $request->boolean('pet');

        $shelters = Shelter::query()->approved()
            ->whereHas('campaigns', fn (Builder $q) => $q->visible())
            ->orderBy('name')->get(['id', 'name', 'city', 'slug']);
        $shelter = $shelterSlug ? $shelters->firstWhere('slug', $shelterSlug) : null;

        $campaigns = DonationCampaign::query()->visible()
            ->when($tab === 'active',
                fn (Builder $q) => $q->acceptingDonations()
                    ->orderByRaw('CASE WHEN ends_at IS NULL THEN 1 ELSE 0 END')->orderBy('ends_at'),
                fn (Builder $q) => $q->whereNot(fn (Builder $w) => $w->acceptingDonations())
                    ->latest('updated_at'))
            ->when($shelter, fn (Builder $q, Shelter $s) => $q->where('shelter_id', $s->id))
            ->when($petOnly, fn (Builder $q) => $q->whereNotNull('pet_id'))
            ->with(['shelter', 'pet.cover'])
            ->orderBy('id')
            ->paginate(self::PER_PAGE)->withQueryString();

        return view('campaigns.index', [
            'campaigns' => $campaigns,
            'tab' => $tab,
            'shelters' => $shelters,
            'shelter' => $shelter,
            'petOnly' => $petOnly,
            // Блок «Помочь приюту без сбора» — все одобренные приюты, а не только те, у кого есть сборы.
            'fundShelters' => Shelter::query()->approved()->orderBy('name')->get(['id', 'name', 'city', 'slug']),
        ]);
    }

    public function show(Request $request, DonationCampaign $campaign): View
    {
        // Чужие черновики и скрытые сборы — «не найдено».
        abort_unless(Gate::allows('view', $campaign), 404);

        $campaign->load(['shelter', 'pet.cover', 'reports' => fn ($q) => $q->whereNotNull('published_at')->with('attachments')]);

        $recent = $campaign->donations()
            ->where('status', PaymentStatus::Succeeded)
            ->latest('paid_at')->latest('id')
            ->limit(self::RECENT_DONATIONS)->get();

        return view('campaigns.show', [
            'campaign' => $campaign,
            'estimate' => Estimate::parse($campaign->description),
            'recent' => $recent,
            'canDonate' => Gate::allows('donate', $campaign),
            'canManage' => $request->user()?->can('update', $campaign) ?? false,
            'prefill' => DonationPrefill::for($request),
        ]);
    }

    public function donations(DonationCampaign $campaign): View
    {
        abort_unless(Gate::allows('view', $campaign), 404);

        $campaign->load('shelter');

        return view('campaigns.donations', [
            'campaign' => $campaign,
            'donations' => $campaign->donations()
                ->where('status', PaymentStatus::Succeeded)
                ->latest('paid_at')->latest('id')
                ->paginate(30),
        ]);
    }
}
