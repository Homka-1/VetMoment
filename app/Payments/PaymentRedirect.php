<?php

namespace App\Payments;

/**
 * Результат создания платежа: куда отправить пользователя.
 */
final readonly class PaymentRedirect
{
    public function __construct(
        public string $url,
    ) {}
}
