<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Shelter;
use App\Models\User;
use App\Payments\InvalidWebhook;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGateways;
use App\Payments\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Жизненный цикл пожертвования (docs/product/user-flows.md, 2):
 * создание `pending` с ключом идемпотентности → платёжная страница провайдера → webhook →
 * в транзакции смена статуса и пересчёт raised_amount / donations_count сбора → «Цель достигнута».
 *
 * Сумма сбора меняется только здесь и только переходами в/из `succeeded`.
 */
class DonationPayments
{
    /** Допустимые переходы статуса платежа по webhook. */
    public const TRANSITIONS = [
        'pending' => [PaymentStatus::Succeeded, PaymentStatus::Failed, PaymentStatus::Expired],
        // Банк может провести платёж уже после того, как мы пометили его истёкшим или неудачным.
        'expired' => [PaymentStatus::Succeeded, PaymentStatus::Failed],
        'failed' => [PaymentStatus::Succeeded],
        'succeeded' => [PaymentStatus::Refunded],
        'refunded' => [],
    ];

    /** Результат обработки webhook — для журнала и тестов. */
    public const APPLIED = 'applied';

    public const DUPLICATE = 'duplicate';

    public const IGNORED = 'ignored';

    public function __construct(private readonly PaymentGateways $gateways) {}

    /**
     * Создаёт пожертвование `pending`. Повторная отправка той же формы (тот же ключ идемпотентности)
     * возвращает уже созданное пожертвование, а не второе.
     *
     * @param  array{amount: int, donor_name: ?string, donor_email: string, is_anonymous: bool, comment: ?string}  $data  amount — в тиынах
     */
    public function start(array $data, Shelter $shelter, ?DonationCampaign $campaign, ?User $user, string $idempotencyKey): Donation
    {
        if ($existing = Donation::query()->where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        try {
            return Donation::create([
                'campaign_id' => $campaign?->id,
                'shelter_id' => $shelter->id,
                'user_id' => $user?->id,
                'donor_name' => $data['donor_name'],
                'donor_email' => $data['donor_email'],
                'is_anonymous' => $data['is_anonymous'],
                'amount' => $data['amount'],
                'comment' => $data['comment'],
                'status' => PaymentStatus::Pending,
                'provider' => $this->gateways->default()->name(),
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Две одновременные отправки одной формы: вторая получает пожертвование первой.
            return Donation::query()->where('idempotency_key', $idempotencyKey)->first() ?? throw $e;
        }
    }

    /** Адрес платёжной страницы провайдера для ожидающего оплаты пожертвования. */
    public function paymentUrl(Donation $donation): string
    {
        return $this->gateways->get($donation->provider)
            ->createPayment($donation, route('donations.show', $donation))->url;
    }

    /**
     * Webhook провайдера: проверка подписи (в шлюзе) → применение статуса.
     *
     * @throws InvalidWebhook
     */
    public function handleWebhook(PaymentGateway $gateway, Request $request): string
    {
        $event = $gateway->parseWebhook($request);
        $result = $this->apply($gateway->name(), $event);

        Log::info('Webhook платежа', [
            'provider' => $gateway->name(),
            'donation' => $event->donationUuid,
            'transaction' => $event->providerPaymentId,
            'status' => $event->status->value,
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Применяет статус из webhook. Идемпотентно: повтор того же уведомления ничего не меняет,
     * один платёж провайдера (provider + provider_payment_id) не может подтвердить два пожертвования.
     */
    public function apply(string $provider, WebhookEvent $event): string
    {
        return DB::transaction(function () use ($provider, $event): string {
            $donation = Donation::query()
                ->where('uuid', $event->donationUuid)
                ->where('provider', $provider)
                ->lockForUpdate()
                ->first();

            if ($donation === null) {
                return self::IGNORED;
            }

            if ($donation->status === $event->status
                && ($donation->provider_payment_id === null || $donation->provider_payment_id === $event->providerPaymentId)) {
                return self::DUPLICATE;
            }

            if (! in_array($event->status, self::TRANSITIONS[$donation->status->value], true)) {
                return self::IGNORED;
            }

            // Возврат относится к уже учтённому платежу — id должен совпасть.
            if ($event->status === PaymentStatus::Refunded && $donation->provider_payment_id !== $event->providerPaymentId) {
                return self::IGNORED;
            }

            // Провайдер сообщил сумму, отличную от нашей, — не засчитываем, разбирается приют.
            if ($event->status === PaymentStatus::Succeeded && $event->amount !== null && $event->amount !== $donation->amount) {
                Log::warning('Сумма платежа не совпала с пожертвованием', ['donation' => $donation->uuid, 'amount' => $event->amount]);

                return self::IGNORED;
            }

            $takenByOther = Donation::query()
                ->where('provider', $provider)
                ->where('provider_payment_id', $event->providerPaymentId)
                ->whereKeyNot($donation->getKey())
                ->exists();

            if ($takenByOther) {
                return self::IGNORED;
            }

            $countsChanged = $donation->status === PaymentStatus::Succeeded || $event->status === PaymentStatus::Succeeded;

            $donation->forceFill([
                'status' => $event->status,
                'provider_payment_id' => $event->providerPaymentId,
                'paid_at' => $event->status === PaymentStatus::Succeeded ? now() : $donation->paid_at,
                'failure_reason' => $event->status === PaymentStatus::Failed
                    ? mb_substr((string) ($event->failureReason ?: 'Банк отклонил платёж.'), 0, 255)
                    : null,
            ])->save();

            if ($countsChanged && $donation->campaign_id !== null) {
                $this->recalculate($donation->campaign_id);
            }

            return self::APPLIED;
        });
    }

    /** Пересчёт сумм сбора под блокировкой строки сбора и перевод в «Цель достигнута». */
    public function recalculate(int $campaignId): void
    {
        DB::transaction(function () use ($campaignId): void {
            $campaign = DonationCampaign::query()->lockForUpdate()->find($campaignId);

            if ($campaign !== null) {
                $campaign->recalculateTotals();
                $campaign->syncCompletion();
            }
        });
    }

    /**
     * Неоплаченные дольше config('payments.pending_ttl_minutes') → «Срок оплаты истёк».
     * Сумм сборов не касается: ожидающие платежи в них не входят.
     */
    public function expireStale(): int
    {
        return Donation::query()
            ->where('status', PaymentStatus::Pending)
            ->where('created_at', '<', now()->subMinutes((int) config('payments.pending_ttl_minutes', 60)))
            ->update(['status' => PaymentStatus::Expired, 'updated_at' => now()]);
    }

    /** Идущие сборы с истёкшим сроком → «Завершён». */
    public function closeExpiredCampaigns(): int
    {
        return DonationCampaign::query()
            ->where('status', CampaignStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->update(['status' => CampaignStatus::Closed, 'updated_at' => now()]);
    }
}
