<?php

namespace App\Payments;

use App\Models\Donation;
use Illuminate\Http\Request;

/**
 * Платёжный провайдер. Сервис DonationPayments не знает деталей провайдера:
 * он создаёт платёж (получает адрес платёжной страницы) и принимает разобранный webhook.
 */
interface PaymentGateway
{
    /** Ключ провайдера — пишется в donations.provider и в адрес webhook: /payments/webhook/{name}. */
    public function name(): string;

    /**
     * Создаёт платёж у провайдера и возвращает адрес платёжной страницы.
     * $returnUrl — куда провайдер вернёт пользователя (страница результата /donations/{uuid}).
     *
     * @throws PaymentException если провайдер недоступен или отказал в создании платежа
     */
    public function createPayment(Donation $donation, string $returnUrl): PaymentRedirect;

    /**
     * Проверяет подпись webhook и переводит его в общий вид.
     *
     * @throws InvalidWebhook если подпись неверна или данные неполные
     */
    public function parseWebhook(Request $request): WebhookEvent;

    /** Ответ провайдеру на принятый webhook (у CloudPayments — {"code": 0}). */
    public function webhookResponse(): array;
}
