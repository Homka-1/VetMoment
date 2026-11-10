<?php

namespace App\Payments;

use App\Enums\PaymentStatus;
use App\Models\Donation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * ЗАГОТОВКА драйвера CloudPayments (Казахстан, валюта KZT). На реальном API не проверялась —
 * перед включением сверить с документацией провайдера и пройти тестовые платежи.
 *
 * - Создание платежа: POST {api_url}/orders/create (Basic auth: Public ID + API Secret) → ссылка на оплату.
 * - Уведомления Pay / Fail / Refund настраиваются в личном кабинете провайдера на адрес
 *   /payments/webhook/cloudpayments_kz?type=pay|fail|refund; подпись — заголовок Content-HMAC
 *   (base64 HMAC-SHA256 тела запроса ключом API Secret).
 * - InvoiceId = uuid пожертвования, TransactionId — id платежа у провайдера (идемпотентность).
 */
class CloudPaymentsGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'cloudpayments_kz';
    }

    public function createPayment(Donation $donation, string $returnUrl): PaymentRedirect
    {
        [$publicId, $secret] = $this->credentials();

        try {
            $response = Http::withBasicAuth($publicId, $secret)->acceptJson()->timeout(15)
                ->post(rtrim((string) config('payments.gateways.cloudpayments_kz.api_url'), '/').'/orders/create', [
                    'Amount' => $donation->amount / 100,
                    'Currency' => 'KZT',
                    'Description' => $donation->campaign?->title ?? 'Пожертвование приюту «'.$donation->shelter->name.'»',
                    'Email' => $donation->donor_email,
                    'InvoiceId' => $donation->uuid,
                    'AccountId' => $donation->user_id ? 'user-'.$donation->user_id : $donation->donor_email,
                    'SuccessRedirectUrl' => $returnUrl,
                    'FailRedirectUrl' => $returnUrl,
                ]);
        } catch (ConnectionException $e) {
            throw new PaymentException('Платёжный сервис недоступен.', previous: $e);
        }

        $url = $response->json('Model.Url');

        if (! $response->successful() || $response->json('Success') !== true || ! is_string($url)) {
            throw new PaymentException('Платёжный сервис отказал в создании платежа: '.$response->json('Message', $response->status()));
        }

        return new PaymentRedirect($url);
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        [, $secret] = $this->credentials();
        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        if (! hash_equals($expected, (string) $request->header('Content-HMAC'))) {
            throw new InvalidWebhook('Неверная подпись webhook.');
        }

        $type = (string) $request->query('type');
        $status = match ($type) {
            'pay' => PaymentStatus::Succeeded,
            'fail' => PaymentStatus::Failed,
            'refund' => PaymentStatus::Refunded,
            default => throw new InvalidWebhook("Неизвестный тип уведомления «{$type}»."),
        };

        // В уведомлении о возврате id исходного платежа — PaymentTransactionId.
        $transactionId = $type === 'refund' ? $request->input('PaymentTransactionId') : $request->input('TransactionId');

        if (blank($request->input('InvoiceId')) || blank($transactionId)) {
            throw new InvalidWebhook('Неполные данные webhook.');
        }

        return new WebhookEvent(
            donationUuid: (string) $request->input('InvoiceId'),
            providerPaymentId: (string) $transactionId,
            status: $status,
            amount: $request->filled('Amount') ? (int) round((float) $request->input('Amount') * 100) : null,
            failureReason: $type === 'fail' ? (string) ($request->input('CardHolderMessage') ?: $request->input('Reason')) : null,
        );
    }

    public function webhookResponse(): array
    {
        return ['code' => 0];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function credentials(): array
    {
        $publicId = (string) config('payments.gateways.cloudpayments_kz.public_id');
        $secret = (string) config('payments.gateways.cloudpayments_kz.api_secret');

        if ($publicId === '' || $secret === '') {
            throw new PaymentException('Не заданы CLOUDPAYMENTS_PUBLIC_ID и CLOUDPAYMENTS_API_SECRET.');
        }

        return [$publicId, $secret];
    }
}
