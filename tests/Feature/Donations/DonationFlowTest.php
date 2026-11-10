<?php

namespace Tests\Feature\Donations;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Shelter;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Сценарий «Пожертвовать на сбор» (docs/product/user-flows.md, 2): форма → pending → демо-банк → результат.
 */
class DonationFlowTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    private DonationCampaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaign = DonationCampaign::factory()->create(['goal_amount' => 100_000 * 100, 'ends_at' => now()->addDays(10)]);
    }

    /** @return array<string, mixed> */
    private function form(array $overrides = []): array
    {
        return [
            'amount' => '5 000',
            'donor_email' => 'aigerim@mail.kz',
            'donor_name' => 'Айгерим',
            'agree' => '1',
            'idempotency_key' => (string) Str::uuid(),
            ...$overrides,
        ];
    }

    public function test_guest_donates_through_the_demo_bank(): void
    {
        $response = $this->post(route('campaigns.donate.store', $this->campaign), $this->form());

        $donation = Donation::sole();
        $response->assertRedirect(route('payments.fake.show', $donation));
        $this->assertSame(PaymentStatus::Pending, $donation->status);
        $this->assertSame(500_000, $donation->amount);
        $this->assertSame($this->campaign->shelter_id, $donation->shelter_id);
        $this->assertNull($donation->user_id);
        $this->assertNull($donation->provider_payment_id);
        $this->assertSame(0, $this->campaign->fresh()->raised_amount, 'ожидающий платёж не входит в сумму');

        $this->get(route('payments.fake.show', $donation))->assertOk()->assertSee('Учебная страница банка')->assertSee('Оплатить '.Money::format(500_000));
        $this->get(route('donations.show', $donation))->assertOk()->assertSee('Ждём ответ банка')->assertSee('http-equiv="refresh"', false);

        $this->post(route('payments.fake.pay', $donation), ['outcome' => 'success'])
            ->assertRedirect(route('donations.show', $donation));

        $donation->refresh();
        $this->assertSame(PaymentStatus::Succeeded, $donation->status);
        $this->assertNotNull($donation->paid_at);
        $this->assertStringStartsWith('fake_', $donation->provider_payment_id);
        $this->assertSame(500_000, $this->campaign->fresh()->raised_amount);
        $this->assertSame(1, $this->campaign->fresh()->donations_count);

        // Тот, кто платил, видит обращение по имени
        $this->get(route('donations.show', $donation))->assertOk()
            ->assertSee('Спасибо, Айгерим!')->assertSee('Оплачено');
        // По ссылке без сессии — без имени и почты
        $this->flushSession();
        $this->get(route('donations.show', $donation))->assertOk()->assertSee('Спасибо!')
            ->assertDontSee('Айгерим')->assertDontSee('aigerim@mail.kz');

        // Оплаченный платёж не открывает демо-банк повторно
        $this->post(route('payments.fake.pay', $donation), ['outcome' => 'decline']);
        $this->assertSame(PaymentStatus::Succeeded, $donation->fresh()->status);
    }

    public function test_declined_payment_shows_the_reason_and_retry_prefills_the_form(): void
    {
        $this->post(route('campaigns.donate.store', $this->campaign), $this->form(['comment' => 'Держись!', 'is_anonymous' => '1']));
        $donation = Donation::sole();

        $this->post(route('payments.fake.pay', $donation), ['outcome' => 'decline']);

        $donation->refresh();
        $this->assertSame(PaymentStatus::Failed, $donation->status);
        $this->assertStringContainsString('недостаточно средств', $donation->failure_reason);
        $this->assertSame(0, $this->campaign->fresh()->raised_amount);

        $this->get(route('donations.show', $donation))->assertOk()
            ->assertSee('Платёж не прошёл')->assertSee('недостаточно средств')->assertSee('Попробовать снова');

        $this->get(route('campaigns.donate', [$this->campaign, 'retry' => $donation->uuid]))->assertOk()
            ->assertSee('value="5 000"', false)->assertSee('aigerim@mail.kz')->assertSee('Держись!');
    }

    public function test_retry_does_not_leak_someone_elses_donation(): void
    {
        $foreign = Donation::factory()->forCampaign($this->campaign)->create(['donor_email' => 'secret@mail.kz']);

        $this->get(route('campaigns.donate', [$this->campaign, 'retry' => $foreign->uuid]))->assertOk()->assertDontSee('secret@mail.kz');
    }

    public function test_double_submit_creates_one_donation(): void
    {
        $form = $this->form();

        $this->post(route('campaigns.donate.store', $this->campaign), $form);
        $this->post(route('campaigns.donate.store', $this->campaign), $form);
        $this->assertSame(1, Donation::count());

        // Уже оплаченная форма отправлена ещё раз — на страницу результата, без нового платежа
        $donation = Donation::sole();
        $this->post(route('payments.fake.pay', $donation), ['outcome' => 'success']);
        $this->post(route('campaigns.donate.store', $this->campaign), $form)->assertRedirect(route('donations.show', $donation));
        $this->assertSame(1, Donation::count());
    }

    public function test_logged_in_user_donation_is_linked_and_form_is_prefilled(): void
    {
        $user = $this->volunteer(['name' => 'Ержан', 'email' => 'erzhan@mail.kz']);

        $this->actingAs($user)->get(route('campaigns.show', $this->campaign))->assertOk()
            ->assertSee('value="erzhan@mail.kz"', false)->assertSee('value="Ержан"', false);

        $this->actingAs($user)->post(route('campaigns.donate.store', $this->campaign), $this->form(['amount' => '', 'amount_preset' => '2000']));

        $donation = Donation::sole();
        $this->assertTrue($donation->user->is($user));
        $this->assertSame(200_000, $donation->amount, 'без JS сумма берётся из выбранного пресета');
    }

    public function test_validation_messages(): void
    {
        $this->from(route('campaigns.show', $this->campaign))
            ->post(route('campaigns.donate.store', $this->campaign), $this->form(['amount' => '150', 'donor_email' => 'aigerim@mail', 'agree' => null]))
            ->assertRedirect(route('campaigns.show', $this->campaign))
            ->assertSessionHasErrors([
                'amount' => 'Минимум '.Money::format(20_000).'. Увеличьте сумму или выберите готовую.',
                'donor_email' => 'В адресе нет домена: например, aigerim@mail.kz',
                'agree',
            ]);

        $this->post(route('campaigns.donate.store', $this->campaign), $this->form(['amount' => '5 000 001']))
            ->assertSessionHasErrors('amount');
        $this->post(route('campaigns.donate.store', $this->campaign), $this->form(['amount' => '100,50']))
            ->assertSessionHasErrors('amount');
        $this->post(route('campaigns.donate.store', $this->campaign), $this->form(['idempotency_key' => 'не-uuid']))
            ->assertSessionHasErrors('idempotency_key');

        $this->assertSame(0, Donation::count());
    }

    public function test_closed_expired_or_hidden_campaigns_do_not_accept_donations(): void
    {
        $closed = DonationCampaign::factory()->status(CampaignStatus::Closed)->create();
        $expired = DonationCampaign::factory()->create(['ends_at' => now()->subHour()]);
        $hidden = DonationCampaign::factory()->create(['is_hidden' => true]);
        $draft = DonationCampaign::factory()->status(CampaignStatus::Draft)->create();

        foreach ([$closed, $expired] as $campaign) {
            $this->post(route('campaigns.donate.store', $campaign), $this->form())
                ->assertRedirect(route('campaigns.show', $campaign))->assertSessionHas('error');
            $this->get(route('campaigns.donate', $campaign))->assertRedirect(route('campaigns.show', $campaign));
            $this->get(route('campaigns.show', $campaign))->assertOk()->assertSee('Сбор закрыт')->assertSee('Помочь приюту');
        }

        foreach ([$hidden, $draft] as $campaign) {
            $this->post(route('campaigns.donate.store', $campaign), $this->form())->assertNotFound();
            $this->get(route('campaigns.donate', $campaign))->assertNotFound();
        }

        $this->assertSame(0, Donation::count());
    }

    public function test_reached_goal_accepts_more_only_with_overfunding(): void
    {
        $strict = DonationCampaign::factory()->status(CampaignStatus::Completed)->create(['goal_amount' => 1_000_000, 'raised_amount' => 1_000_000]);
        $open = DonationCampaign::factory()->status(CampaignStatus::Completed)->create([
            'goal_amount' => 1_000_000, 'raised_amount' => 1_000_000, 'allow_overfunding' => true, 'ends_at' => now()->addWeek(),
        ]);

        $this->get(route('campaigns.show', $strict))->assertOk()->assertSee('Цель собрана — спасибо!')->assertDontSee('name="idempotency_key"', false);
        $this->get(route('campaigns.show', $open))->assertOk()->assertSee('Приют принимает помощь сверх цели');

        $this->post(route('campaigns.donate.store', $strict), $this->form())->assertSessionHas('error');
        $this->post(route('campaigns.donate.store', $open), $this->form())->assertRedirect();
        $this->assertSame(1, Donation::count());
    }

    public function test_donation_to_the_shelter_fund(): void
    {
        $shelter = Shelter::factory()->create();

        $this->get(route('donate.fund', ['shelter' => $shelter->slug]))->assertRedirect(route('shelters.donate', $shelter));
        $this->get(route('donate.fund', ['shelter' => 'nope']))->assertRedirect(route('campaigns.index'));

        $this->get(route('shelters.donate', $shelter))->assertOk()->assertSee('общий фонд приюта');
        $this->post(route('shelters.donate.store', $shelter), $this->form());

        $donation = Donation::sole();
        $this->assertNull($donation->campaign_id);
        $this->assertSame($shelter->id, $donation->shelter_id);

        $this->post(route('payments.fake.pay', $donation), ['outcome' => 'success']);
        $this->get(route('donations.show', $donation))->assertSee('общий фонд приюта «'.$shelter->name.'»');

        $pending = Shelter::factory()->pending()->create();
        $this->get(route('shelters.donate', $pending))->assertNotFound();
        $this->post(route('shelters.donate.store', $pending), $this->form())->assertNotFound();
    }

    public function test_demo_bank_is_only_for_the_fake_provider(): void
    {
        $donation = Donation::factory()->forCampaign($this->campaign)->status(PaymentStatus::Pending)
            ->create(['provider' => 'cloudpayments_kz', 'provider_payment_id' => null]);

        $this->get(route('payments.fake.show', $donation))->assertNotFound();
        $this->post(route('payments.fake.pay', $donation), ['outcome' => 'success'])->assertNotFound();
        $this->assertSame(PaymentStatus::Pending, $donation->fresh()->status);
    }

    public function test_unavailable_provider_keeps_the_form_and_explains(): void
    {
        config(['payments.default' => 'cloudpayments_kz']); // ключи не заданы — провайдер «не отвечает»

        $this->from(route('campaigns.show', $this->campaign))
            ->post(route('campaigns.donate.store', $this->campaign), $this->form())
            ->assertRedirect(route('campaigns.show', $this->campaign))
            ->assertSessionHas('error')
            ->assertSessionHasInput('donor_email', 'aigerim@mail.kz');

        $this->assertSame('cloudpayments_kz', Donation::sole()->provider);
    }
}
