<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Payments\InvalidWebhook;
use App\Payments\PaymentGateways;
use App\Services\DonationPayments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /payments/webhook/{provider} — уведомления провайдера о платежах (без CSRF, с проверкой подписи).
 * Повтор уведомления отвечает тем же успехом: провайдер перестаёт его слать, а данные не меняются.
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentGateways $gateways, DonationPayments $payments): JsonResponse
    {
        abort_unless($gateways->has($provider), 404);

        $gateway = $gateways->get($provider);

        try {
            $payments->handleWebhook($gateway, $request);
        } catch (InvalidWebhook $e) {
            Log::warning('Отклонён webhook платежа', ['provider' => $provider, 'reason' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json($gateway->webhookResponse());
    }
}
