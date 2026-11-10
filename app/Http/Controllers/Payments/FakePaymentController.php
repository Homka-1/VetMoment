<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Payments\FakePaymentGateway;
use App\Services\DonationPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Демо-страница «банка» для FakePaymentGateway: «Оплатить» / «Отказ».
 * Нажатие кнопки = подписанный webhook, который проходит тот же путь, что от настоящего провайдера.
 */
class FakePaymentController extends Controller
{
    public function show(Donation $donation): View|RedirectResponse
    {
        abort_unless($donation->provider === 'fake', 404);

        if (! $donation->status->isPending()) {
            return redirect()->route('donations.show', $donation);
        }

        $donation->load(['campaign', 'shelter']);

        return view('payments.fake', ['donation' => $donation]);
    }

    public function pay(Request $request, Donation $donation, FakePaymentGateway $gateway, DonationPayments $payments): RedirectResponse
    {
        abort_unless($donation->provider === 'fake', 404);

        $outcome = $request->validate(['outcome' => ['required', Rule::in(['success', 'decline'])]])['outcome'];
        $webhook = $gateway->webhookFor($donation, $outcome === 'success' ? PaymentStatus::Succeeded : PaymentStatus::Failed);

        $payments->handleWebhook($gateway, Request::create(
            route('payments.webhook', 'fake'), 'POST', server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_'.strtoupper(str_replace('-', '_', FakePaymentGateway::SIGNATURE_HEADER)) => $webhook['signature'],
            ], content: $webhook['body'],
        ));

        return redirect()->route('donations.show', $donation);
    }
}
