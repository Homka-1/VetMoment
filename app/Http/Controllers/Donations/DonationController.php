<?php

namespace App\Http\Controllers\Donations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Donations\DonationRequest;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Shelter;
use App\Payments\PaymentException;
use App\Services\DonationPayments;
use App\Support\DonationPrefill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Пожертвование: форма на сбор (/campaigns/{slug}/donate) и в общий фонд приюта (/shelters/{slug}/donate),
 * создание платежа и страница результата /donations/{uuid} (макет 05: оплачено / ожидание / ошибка).
 */
class DonationController extends Controller
{
    public function campaignForm(Request $request, DonationCampaign $campaign): View|RedirectResponse
    {
        abort_unless(Gate::allows('view', $campaign), 404);

        if (Gate::denies('donate', $campaign)) {
            return redirect()->route('campaigns.show', $campaign);
        }

        $campaign->load(['shelter', 'pet.cover']);

        return view('donations.create', [
            'campaign' => $campaign,
            'shelter' => $campaign->shelter,
            'prefill' => DonationPrefill::for($request),
        ]);
    }

    public function shelterForm(Request $request, Shelter $shelter): View
    {
        abort_unless(Gate::allows('donate', $shelter), 404);

        return view('donations.create', [
            'campaign' => null,
            'shelter' => $shelter,
            'prefill' => DonationPrefill::for($request),
        ]);
    }

    /** Блок «Помочь приюту без сбора» на /campaigns: GET-форма с выбором приюта. */
    public function chooseFund(Request $request): RedirectResponse
    {
        $shelter = Shelter::query()->approved()->where('slug', (string) $request->query('shelter'))->first();

        return $shelter
            ? redirect()->route('shelters.donate', $shelter)
            : redirect()->route('campaigns.index');
    }

    public function storeForCampaign(DonationRequest $request, DonationCampaign $campaign, DonationPayments $payments): RedirectResponse
    {
        abort_unless(Gate::allows('view', $campaign), 404);

        if (Gate::denies('donate', $campaign)) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Сбор уже не принимает пожертвования. Можно помочь приюту в общий фонд.');
        }

        return $this->start($request, $payments, $campaign->shelter, $campaign);
    }

    public function storeForShelter(DonationRequest $request, Shelter $shelter, DonationPayments $payments): RedirectResponse
    {
        abort_unless(Gate::allows('donate', $shelter), 404);

        return $this->start($request, $payments, $shelter, null);
    }

    public function show(Request $request, Donation $donation): View
    {
        $donation->load(['campaign.pet', 'shelter']);

        return view('donations.show', [
            'donation' => $donation,
            'owner' => DonationPrefill::owns($request, $donation->uuid, $donation),
        ]);
    }

    private function start(DonationRequest $request, DonationPayments $payments, Shelter $shelter, ?DonationCampaign $campaign): RedirectResponse
    {
        $donation = $payments->start(
            $request->donation(), $shelter, $campaign, $request->user(), (string) $request->validated('idempotency_key'),
        );

        DonationPrefill::remember($request, $donation);

        // Повторная отправка уже оплаченной или отклонённой формы — сразу на результат.
        if (! $donation->status->isPending()) {
            return redirect()->route('donations.show', $donation);
        }

        try {
            return redirect()->away($payments->paymentUrl($donation));
        } catch (PaymentException $e) {
            Log::error('Не удалось создать платёж', ['donation' => $donation->uuid, 'error' => $e->getMessage()]);

            return back()->withInput()
                ->with('error', 'Платёжный сервис сейчас не отвечает. Деньги не списаны — попробуйте через несколько минут.');
        }
    }
}
