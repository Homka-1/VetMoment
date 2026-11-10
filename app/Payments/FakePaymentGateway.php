<?php

namespace App\Payments;

use App\Enums\PaymentStatus;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Демо-шлюз для разработки и показа: платёжная страница — /payments/fake/{uuid} с кнопками
 * «Оплатить» и «Отказ». Кнопки формируют подписанный webhook и прогоняют его через тот же путь,
 * что и настоящий провайдер (DonationPayments::handleWebhook), — проверяются подпись, идемпотентность и пересчёт.
 */
class FakePaymentGateway implements PaymentGateway
{
    public const SIGNATURE_HEADER = 'X-Fake-Signature';

    public const DECLINE_REASON = 'Банк отклонил карту: недостаточно средств. Деньги не списаны.';

    public function name(): string
    {
        return 'fake';
    }

    public function createPayment(Donation $donation, string $returnUrl): PaymentRedirect
    {
        return new PaymentRedirect(route('payments.fake.show', $donation));
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $body = $request->getContent();

        if (! hash_equals($this->sign($body), (string) $request->header(self::SIGNATURE_HEADER))) {
            throw new InvalidWebhook('Неверная подпись webhook.');
        }

        $data = json_decode($body, true);
        $status = is_array($data) ? PaymentStatus::tryFrom((string) ($data['status'] ?? '')) : null;

        if ($status === null || blank($data['invoice_id'] ?? null) || blank($data['transaction_id'] ?? null)) {
            throw new InvalidWebhook('Неполные данные webhook.');
        }

        return new WebhookEvent(
            donationUuid: (string) $data['invoice_id'],
            providerPaymentId: (string) $data['transaction_id'],
            status: $status,
            amount: isset($data['amount']) ? (int) $data['amount'] : null,
            failureReason: isset($data['reason']) ? (string) $data['reason'] : null,
        );
    }

    public function webhookResponse(): array
    {
        return ['ok' => true];
    }

    /**
     * Webhook, который «банк» отправил бы после нажатия кнопки на демо-странице.
     *
     * @return array{body: string, signature: string}
     */
    public function webhookFor(Donation $donation, PaymentStatus $status, ?string $transactionId = null): array
    {
        $body = (string) json_encode([
            'invoice_id' => $donation->uuid,
            'transaction_id' => $transactionId ?? $donation->provider_payment_id ?? 'fake_'.Str::lower(Str::random(16)),
            'status' => $status->value,
            'amount' => $donation->amount,
            'reason' => $status === PaymentStatus::Failed ? self::DECLINE_REASON : null,
        ], JSON_UNESCAPED_UNICODE);

        return ['body' => $body, 'signature' => $this->sign($body)];
    }

    public function sign(string $body): string
    {
        return hash_hmac('sha256', $body, $this->secret());
    }

    private function secret(): string
    {
        return (string) (config('payments.gateways.fake.secret') ?: hash('sha256', 'fake-gateway|'.config('app.key')));
    }
}
