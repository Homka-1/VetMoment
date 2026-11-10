<?php

namespace App\Http\Controllers\Shelter;

use App\Enums\CampaignStatus;
use App\Enums\PetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shelter\CampaignRequest;
use App\Models\DonationCampaign;
use App\Models\Shelter;
use App\Services\CampaignEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Сборы в кабинете приюта (/shelter/campaigns): список, создание, правка, закрытие, архив.
 */
class CampaignController extends Controller
{
    /** Фильтры-чипы над таблицей → статусы. */
    public const TABS = [
        'all' => ['label' => 'Все', 'statuses' => null],
        'active' => ['label' => 'Идут', 'statuses' => [CampaignStatus::Active]],
        'draft' => ['label' => 'Черновики', 'statuses' => [CampaignStatus::Draft]],
        'finished' => ['label' => 'Завершены', 'statuses' => [CampaignStatus::Completed, CampaignStatus::Closed]],
        'archived' => ['label' => 'Архив', 'statuses' => [CampaignStatus::Archived]],
    ];

    public function index(Request $request): View
    {
        $shelter = $request->user()->shelter();
        $tab = array_key_exists((string) $request->query('status'), self::TABS) ? (string) $request->query('status') : 'all';

        $counts = $shelter->campaigns()->toBase()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        $campaigns = $shelter->campaigns()
            ->when(self::TABS[$tab]['statuses'], fn (Builder $q, array $statuses) => $q->whereIn('status', $statuses))
            ->when($tab === 'all', fn (Builder $q) => $q->where('status', '!=', CampaignStatus::Archived))
            ->with('pet')
            ->withCount('reports')
            ->latest('updated_at')->latest('id')
            ->paginate(20)->withQueryString();

        $tabs = [];
        foreach (self::TABS as $key => $definition) {
            $count = $definition['statuses'] === null
                ? $counts->except(CampaignStatus::Archived->value)->sum()
                : collect($definition['statuses'])->sum(fn (CampaignStatus $s) => (int) ($counts[$s->value] ?? 0));

            if ($count > 0 || in_array($key, ['all', $tab], true)) {
                $tabs[$key] = ['label' => $definition['label'], 'count' => (int) $count];
            }
        }

        return view('shelter.campaigns.index', compact('shelter', 'campaigns', 'tabs', 'tab'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', DonationCampaign::class);

        $shelter = $request->user()->shelter();

        return view('shelter.campaigns.form', [
            'campaign' => new DonationCampaign(['status' => CampaignStatus::Draft, 'pet_id' => $request->integer('pet') ?: null]),
            'shelter' => $shelter,
            'pets' => $this->petOptions($shelter),
        ]);
    }

    public function store(CampaignRequest $request, CampaignEditor $editor): RedirectResponse
    {
        $campaign = $editor->create($request->shelter(), $request->campaignAttributes(), $request->cover(), $request->wantsPublish());

        return redirect()->route('shelter.campaigns.index')->with('status', $campaign->status === CampaignStatus::Draft
            ? "Черновик «{$campaign->title}» сохранён"
            : "Сбор «{$campaign->title}» запущен");
    }

    public function edit(DonationCampaign $campaign): View
    {
        Gate::authorize('update', $campaign);

        $campaign->load('shelter');

        return view('shelter.campaigns.form', [
            'campaign' => $campaign,
            'shelter' => $campaign->shelter,
            'pets' => $this->petOptions($campaign->shelter),
        ]);
    }

    public function update(CampaignRequest $request, DonationCampaign $campaign, CampaignEditor $editor): RedirectResponse
    {
        $wasDraft = $campaign->status === CampaignStatus::Draft;
        $campaign = $editor->update($campaign, $request->campaignAttributes(), $request->cover(),
            (bool) $request->validated('remove_cover'), $request->wantsPublish());

        return redirect()->route('shelter.campaigns.edit', $campaign)->with('status', $wasDraft && $campaign->status !== CampaignStatus::Draft
            ? "Сбор «{$campaign->title}» запущен"
            : 'Изменения сохранены');
    }

    public function close(DonationCampaign $campaign, CampaignEditor $editor): RedirectResponse
    {
        Gate::authorize('update', $campaign);
        abort_unless(in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Completed], true), 422);

        $editor->close($campaign);

        return back()->with('status', "Сбор «{$campaign->title}» закрыт");
    }

    public function destroy(DonationCampaign $campaign, CampaignEditor $editor): RedirectResponse
    {
        Gate::authorize('delete', $campaign);

        $editor->archive($campaign);

        return redirect()->route('shelter.campaigns.index')->with('status', "Сбор «{$campaign->title}» перенесён в архив");
    }

    /**
     * @return array<int, string>
     */
    private function petOptions(Shelter $shelter): array
    {
        return $shelter->pets()->where('status', '!=', PetStatus::Archived)
            ->orderBy('name')->pluck('name', 'id')->all();
    }
}
