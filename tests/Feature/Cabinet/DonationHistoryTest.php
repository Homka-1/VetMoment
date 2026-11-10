<?php

namespace Tests\Feature\Cabinet;

use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * «Мои пожертвования» в кабинете: только свои, итог — только оплаченные.
 */
class DonationHistoryTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_history_shows_own_donations(): void
    {
        $user = $this->volunteer();
        $campaign = DonationCampaign::factory()->create(['title' => 'Корм на зиму']);
        Donation::factory()->forCampaign($campaign)->create(['user_id' => $user->id, 'amount' => 5_000 * 100]);
        Donation::factory()->forCampaign($campaign)->status(PaymentStatus::Failed)->create(['user_id' => $user->id, 'amount' => 3_000 * 100]);
        Donation::factory()->create(['user_id' => $user->id, 'amount' => 1_000 * 100]);
        Donation::factory()->forCampaign(DonationCampaign::factory()->create(['title' => 'Чужая помощь']))->create();

        $this->get('/cabinet/donations')->assertRedirect('/login');

        $this->actingAs($user)->get('/cabinet/donations')->assertOk()
            ->assertSee('Корм на зиму')->assertSee('Общий фонд приюта')->assertDontSee('Чужая помощь')
            ->assertSee('Ошибка оплаты')
            ->assertSee(Money::format(600_000));
    }

    public function test_empty_history(): void
    {
        $this->actingAs($this->volunteer())->get('/cabinet/donations')->assertOk()->assertSee('Смотреть сборы');
    }
}
