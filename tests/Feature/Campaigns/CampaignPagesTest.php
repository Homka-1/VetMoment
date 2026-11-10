<?php

namespace Tests\Feature\Campaigns;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Публичные страницы сборов: список с фильтрами (макет 04), страница сбора (макет 05), все пожертвования.
 */
class CampaignPagesTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_list_shows_active_and_finished_campaigns_separately(): void
    {
        $active = DonationCampaign::factory()->create(['title' => 'Корм на зиму']);
        $overfunding = DonationCampaign::factory()->status(CampaignStatus::Completed)
            ->create(['title' => 'Сверх цели', 'allow_overfunding' => true, 'ends_at' => now()->addWeek()]);
        $completed = DonationCampaign::factory()->status(CampaignStatus::Completed)->create(['title' => 'Вакцины собраны']);
        $expired = DonationCampaign::factory()->create(['title' => 'Срок прошёл', 'ends_at' => now()->subDay()]);
        DonationCampaign::factory()->status(CampaignStatus::Draft)->create(['title' => 'Черновик сбора']);
        DonationCampaign::factory()->create(['title' => 'Скрытый сбор', 'is_hidden' => true]);
        DonationCampaign::factory()->for(Shelter::factory()->pending())->create(['title' => 'Сбор приюта на проверке']);

        $this->get('/campaigns')->assertOk()
            ->assertSee($active->title)->assertSee($overfunding->title)
            ->assertDontSee($completed->title)->assertDontSee($expired->title)
            ->assertDontSee('Черновик сбора')->assertDontSee('Скрытый сбор')->assertDontSee('Сбор приюта на проверке')
            ->assertSee('Помочь приюту без сбора');

        $this->get('/campaigns?tab=finished')->assertOk()
            ->assertSee($completed->title)->assertSee($expired->title)
            ->assertDontSee($active->title)->assertDontSee('Помочь приюту без сбора');
    }

    public function test_filters_by_shelter_and_pet(): void
    {
        $shelter = Shelter::factory()->create(['name' => 'Хвостики']);
        $pet = Pet::factory()->for($shelter)->create();
        $forPet = DonationCampaign::factory()->for($shelter)->create(['pet_id' => $pet->id, 'title' => 'Лечение Графа']);
        $general = DonationCampaign::factory()->for($shelter)->create(['title' => 'Новые вольеры']);
        $other = DonationCampaign::factory()->create(['title' => 'Чужой сбор']);

        $this->get('/campaigns?shelter='.$shelter->slug)->assertOk()
            ->assertSee($forPet->title)->assertSee($general->title)->assertDontSee($other->title);
        $this->get('/campaigns?pet=1')->assertOk()
            ->assertSee($forPet->title)->assertDontSee($general->title)->assertDontSee($other->title);
        // Неизвестный приют в ссылке — просто без фильтра
        $this->get('/campaigns?shelter=nope')->assertOk()->assertSee($other->title);
    }

    public function test_campaign_page(): void
    {
        $shelter = Shelter::factory()->create(['name' => 'Дом лап', 'city' => 'Алматы']);
        $pet = Pet::factory()->for($shelter)->create(['name' => 'Ромашка']);
        $campaign = DonationCampaign::factory()->for($shelter)->create([
            'pet_id' => $pet->id,
            'title' => 'Операция на лапе для Ромашки',
            'goal_amount' => 600_000 * 100,
            'description' => "Ромашку сбила машина.\n\nСмета:\n— операция и наркоз — 380 000 ₸\n— пластина и спицы — 120 000 ₸",
        ]);
        Donation::factory()->forCampaign($campaign)->create(['donor_name' => 'Ержан', 'amount' => 10_000 * 100, 'comment' => 'Выздоравливай!']);
        Donation::factory()->forCampaign($campaign)->anonymous()->create(['donor_name' => 'Тайный', 'amount' => 2_000 * 100, 'comment' => 'Секрет']);
        Donation::factory()->forCampaign($campaign)->status(PaymentStatus::Failed)->create(['donor_name' => 'Неудачник']);
        $campaign->recalculateTotals();
        $report = $campaign->reports()->create([
            'title' => 'Первичный осмотр', 'body' => "Осмотрели.\n— рентген — 18 000 ₸", 'published_at' => now()->subDay(),
        ]);
        $campaign->reports()->create(['title' => 'Неопубликованный', 'body' => 'Черновик отчёта', 'published_at' => null]);

        $this->get(route('campaigns.show', $campaign))->assertOk()
            ->assertSee('Операция на лапе для Ромашки')
            ->assertSee('Дом лап, Алматы')
            ->assertSee(route('pets.show', $pet))
            ->assertSee(Money::format(1_200_000))
            ->assertSee('Ромашку сбила машина.')
            ->assertDontSee('Смета:')
            ->assertSee('На что нужны деньги')->assertSee('Операция и наркоз')->assertSee(Money::format(38_000_000))
            ->assertSee($report->title)->assertSee('Рентген')->assertDontSee('Неопубликованный')
            ->assertSee('Ержан')->assertSee('Выздоравливай!')
            ->assertSee('Аноним')->assertDontSee('Тайный')->assertDontSee('Секрет')
            ->assertDontSee('Неудачник')
            ->assertSee('name="idempotency_key"', false)
            ->assertSee('Помочь сбору');

        $this->get(route('campaigns.donations', $campaign))->assertOk()
            ->assertSee('Ержан')->assertSee('Аноним')->assertDontSee('Тайный')->assertDontSee('Неудачник');
    }

    public function test_drafts_are_visible_only_to_the_shelter_and_admin(): void
    {
        $campaign = DonationCampaign::factory()->status(CampaignStatus::Draft)->create();

        $this->get(route('campaigns.show', $campaign))->assertNotFound();
        $this->actingAs($this->volunteer())->get(route('campaigns.show', $campaign))->assertNotFound();
        $this->actingAs($this->staffOf($campaign->shelter))->get(route('campaigns.show', $campaign))->assertOk()
            ->assertSee('Сбор не виден на сайте')->assertSee('noindex')->assertSee('Редактировать');
        $this->actingAs($this->admin())->get(route('campaigns.show', $campaign))->assertOk();
    }

    public function test_pet_page_links_to_the_active_campaign_or_shelter_fund(): void
    {
        $pet = Pet::factory()->create();
        $this->get(route('pets.show', $pet))->assertOk()->assertSee(route('shelters.donate', $pet->shelter));

        $campaign = DonationCampaign::factory()->for($pet->shelter)->create(['pet_id' => $pet->id]);
        $this->get(route('pets.show', $pet))->assertOk()->assertSee(route('campaigns.show', $campaign));
    }

    public function test_header_help_button_leads_to_campaigns(): void
    {
        $this->get('/')->assertOk()->assertSee('href="'.route('campaigns.index').'"', false);
    }
}
