<?php

namespace App\Http\Controllers\Shelter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shelter\CampaignReportRequest;
use App\Models\CampaignReport;
use App\Models\DonationCampaign;
use App\Services\CampaignReports;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Отчёты о расходах сбора (/shelter/campaigns/{campaign}/reports): список, публикация, удаление.
 */
class CampaignReportController extends Controller
{
    public function index(DonationCampaign $campaign): View
    {
        Gate::authorize('manageReports', $campaign);

        $campaign->load(['shelter', 'reports.attachments']);

        return view('shelter.campaigns.reports', ['campaign' => $campaign]);
    }

    public function store(CampaignReportRequest $request, DonationCampaign $campaign, CampaignReports $reports): RedirectResponse
    {
        $spent = $request->validated('spent');

        $reports->publish($campaign, [
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'spent_amount' => $spent === null ? null : (int) $spent * 100,
        ], $request->photos(), $request->receipts());

        return redirect()->route('shelter.campaigns.reports.index', $campaign)->with('status', 'Отчёт опубликован');
    }

    public function destroy(DonationCampaign $campaign, CampaignReport $report, CampaignReports $reports): RedirectResponse
    {
        Gate::authorize('manageReports', $campaign);
        abort_unless($report->campaign_id === $campaign->id, 404);

        $reports->delete($report);

        return redirect()->route('shelter.campaigns.reports.index', $campaign)->with('status', 'Отчёт удалён');
    }
}
