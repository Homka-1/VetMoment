<?php

namespace Tests\Feature\Donations;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Payments\FakePaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Webhook провайдера: подпись, идемпотентность, пересчёт суммы сбора только по оплаченным,
 * «Цель достигнута», возвраты; закрытие сборов по сроку и истечение неоплаченных платежей.
 */
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private DonationCampaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaign = DonationCampaign::factory()->create(['goal_amount' => 10_000 * 100]);
    }

    private function pending(int $tenge = 1_000): Donation
    {
        return Donation::factory()->forCampaign($this->campaign)->status(PaymentStatus::Pending)
            ->create(['amount' => $tenge * 100, 'provider_payment_id' => null]);
    }

    /** @param  array<string, mixed>  $payload */
    private function webhook(array $payload, ?string $signature = null): TestResponse
    {
        $body = (string) json_encode($payload);

        return $this->call('POST', route('payments.webhook', 'fake'), server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FAKE_SIGNATURE' => $signature ?? app(FakePaymentGateway::class)->sign($body),
        ], content: $body);
    }

    private function pay(Donation $donation, string $transaction = 'tx-1', PaymentStatus $status = PaymentStatus::Succeeded, ?int $amount = null): TestResponse
    {
        return $this->webhook([
            'invoice_id' => $donation->uuid,
            'transaction_id' => $transaction,
            'status' => $status->value,
            'amount' => $amount ?? $donation->amount,
        ]);
    }

    public function test_succeeded_webhook_counts_the_donation_without_csrf(): void
    {
        $donation = $this->pending(3_000);

        $this->pay($donation)->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(PaymentStatus::Succeeded, $donation->fresh()->status);
        $this->assertSame('tx-1', $donation->fresh()->provider_payment_id);
        $this->assertSame(300_000, $this->campaign->fresh()->raised_amount);
        $this->assertSame(1, $this->campaign->fresh()->donations_count);
    }

    public function test_repeated_webhook_is_counted_once(): void
    {
        $donation = $this->pending(3_000);

        $this->pay($donation)->assertOk();
        $this->pay($donation)->assertOk();
        $this->pay($donation)->assertOk();

        $this->assertSame(300_000, $this->campaign->fresh()->raised_amount);
        $this->assertSame(1, $this->campaign->fresh()->donations_count);
    }

    public function test_one_provider_payment_cannot_confirm_two_donations(): void
    {
        $first = $this->pending();
        $second = $this->pending();

        $this->pay($first, 'tx-same')->assertOk();
        $this->pay($second, 'tx-same')->assertOk();

        $this->assertSame(PaymentStatus::Pending, $second->fresh()->status);
        $this->assertSame(100_000, $this->campaign->fresh()->raised_amount);
    }

    public function test_bad_signature_and_garbage_are_rejected(): void
    {
        $donation = $this->pending();

        $this->webhook(['invoice_id' => $donation->uuid, 'transaction_id' => 'tx', 'status' => 'succeeded'], 'forged')->assertStatus(400);
        $this->webhook(['invoice_id' => $donation->uuid, 'status' => 'succeeded'])->assertStatus(400);
        $this->webhook(['invoice_id' => $donation->uuid, 'transaction_id' => 'tx', 'status' => 'paid'])->assertStatus(400);
        $this->post(route('payments.webhook', 'unknown-bank'))->assertNotFound();

        $this->assertSame(PaymentStatus::Pending, $donation->fresh()->status);
        $this->assertSame(0, $this->campaign->fresh()->raised_amount);
    }

    public function test_amount_mismatch_and_foreign_provider_are_ignored(): void
    {
        $donation = $this->pending(1_000);
        $this->pay($donation, amount: 100)->assertOk();
        $this->assertSame(PaymentStatus::Pending, $donation->fresh()->status);

        $other = Donation::factory()->forCampaign($this->campaign)->status(PaymentStatus::Pending)
            ->create(['provider' => 'cloudpayments_kz', 'provider_payment_id' => null]);
        $this->pay($other)->assertOk();
        $this->assertSame(PaymentStatus::Pending, $other->fresh()->status);

        $this->webhook(['invoice_id' => '00000000-0000-0000-0000-000000000000', 'transaction_id' => 'tx', 'status' => 'succeeded'])->assertOk();
        $this->assertSame(0, $this->campaign->fresh()->raised_amount);
    }

    public function test_status_transitions(): void
    {
        // Отказ, затем банк всё-таки провёл платёж
        $late = $this->pending();
        $this->pay($late, 'tx-a', PaymentStatus::Failed)->assertOk();
        $this->assertSame(PaymentStatus::Failed, $late->fresh()->status);
        $this->pay($late, 'tx-a2')->assertOk();
        $this->assertSame(PaymentStatus::Succeeded, $late->fresh()->status);
        $this->assertNull($late->fresh()->failure_reason);

        // Оплаченное нельзя «отклонить» задним числом
        $this->pay($late, 'tx-a2', PaymentStatus::Failed)->assertOk();
        $this->assertSame(PaymentStatus::Succeeded, $late->fresh()->status);

        // Возврат уменьшает сумму; возврат чужой транзакции игнорируется
        $this->pay($late, 'tx-other', PaymentStatus::Refunded)->assertOk();
        $this->assertSame(PaymentStatus::Succeeded, $late->fresh()->status);
        $this->pay($late, 'tx-a2', PaymentStatus::Refunded)->assertOk();
        $this->assertSame(PaymentStatus::Refunded, $late->fresh()->status);
        $this->assertSame(0, $this->campaign->fresh()->raised_amount);
        $this->assertSame(0, $this->campaign->fresh()->donations_count);

        // Истёкший платёж, оплаченный позже, засчитывается
        $expired = $this->pending();
        $expired->forceFill(['status' => PaymentStatus::Expired])->save();
        $this->pay($expired, 'tx-b')->assertOk();
        $this->assertSame(PaymentStatus::Succeeded, $expired->fresh()->status);
        $this->assertSame(100_000, $this->campaign->fresh()->raised_amount);
    }

    public function test_reaching_the_goal_completes_the_campaign(): void
    {
        $first = $this->pending(6_000);
        $second = $this->pending(4_000);

        $this->pay($first, 'tx-1');
        $this->assertSame(CampaignStatus::Active, $this->campaign->fresh()->status);

        $this->pay($second, 'tx-2');
        $campaign = $this->campaign->fresh();
        $this->assertSame(CampaignStatus::Completed, $campaign->status);
        $this->assertNotNull($campaign->completed_at);
        $this->assertSame(10_000 * 100, $campaign->raised_amount);
        $this->assertFalse($campaign->acceptsDonations(), 'без «сверх цели» сбор больше не принимает деньги');
    }

    public function test_pending_payment_started_before_completion_still_counts(): void
    {
        $late = $this->pending(2_000);
        $this->pay($this->pending(10_000), 'tx-goal');
        $this->assertSame(CampaignStatus::Completed, $this->campaign->fresh()->status);

        // Платёж начат, пока сбор шёл, — деньги списаны, значит, учитываем
        $this->pay($late, 'tx-late')->assertOk();
        $this->assertSame(12_000 * 100, $this->campaign->fresh()->raised_amount);
    }

    public function test_general_fund_donation_does_not_touch_campaigns(): void
    {
        $fund = Donation::factory()->status(PaymentStatus::Pending)
            ->create(['shelter_id' => $this->campaign->shelter_id, 'provider_payment_id' => null]);

        $this->pay($fund)->assertOk();

        $this->assertSame(PaymentStatus::Succeeded, $fund->fresh()->status);
        $this->assertSame(0, $this->campaign->fresh()->raised_amount);
    }

    public function test_expired_campaigns_are_closed_and_stale_payments_expire(): void
    {
        $expired = DonationCampaign::factory()->create(['ends_at' => now()->subMinute()]);
        $endless = DonationCampaign::factory()->create(['ends_at' => null]);
        $completed = DonationCampaign::factory()->status(CampaignStatus::Completed)->create(['ends_at' => now()->subDay()]);

        $old = $this->pending();
        $old->forceFill(['created_at' => now()->subMinutes(61)])->save();
        $fresh = $this->pending();

        $this->artisan('app:close-expired-campaigns')->assertSuccessful();
        $this->artisan('app:expire-pending-donations')->assertSuccessful();

        $this->assertSame(CampaignStatus::Closed, $expired->fresh()->status);
        $this->assertSame(CampaignStatus::Active, $endless->fresh()->status);
        $this->assertSame(CampaignStatus::Active, $this->campaign->fresh()->status);
        $this->assertSame(CampaignStatus::Completed, $completed->fresh()->status);
        $this->assertSame(PaymentStatus::Expired, $old->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $fresh->fresh()->status);

        $this->get(route('donations.show', $old))->assertOk()->assertSee('Время на оплату вышло')->assertSee('Попробовать снова');
    }
}
