<?php

namespace App\Payments;

use InvalidArgumentException;

/**
 * Реестр платёжных шлюзов: шлюз по умолчанию (config/payments.php) и шлюз по ключу провайдера
 * (для webhook и для пожертвований, созданных другим шлюзом).
 */
class PaymentGateways
{
    /** @var array<string, class-string<PaymentGateway>> */
    public const DRIVERS = [
        'fake' => FakePaymentGateway::class,
        'cloudpayments_kz' => CloudPaymentsGateway::class,
    ];

    public function default(): PaymentGateway
    {
        return $this->get((string) config('payments.default', 'fake'));
    }

    public function get(string $name): PaymentGateway
    {
        if (! array_key_exists($name, self::DRIVERS)) {
            throw new InvalidArgumentException("Неизвестный платёжный шлюз «{$name}».");
        }

        return app(self::DRIVERS[$name]);
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, self::DRIVERS);
    }
}
