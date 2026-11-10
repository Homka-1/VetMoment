<?php

namespace App\Payments;

use App\Enums\PaymentStatus;

/**
 * Уведомление провайдера о платеже в общем виде.
 * $donationUuid — наш идентификатор (InvoiceId), $providerPaymentId — id транзакции у провайдера.
 */
final readonly class WebhookEvent
{
    public function __construct(
        public string $donationUuid,
        public string $providerPaymentId,
        public PaymentStatus $status,
        public ?int $amount = null,
        public ?string $failureReason = null,
    ) {}
}
