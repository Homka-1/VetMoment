<?php

namespace Tests\Feature\Public;

use App\Enums\CampaignStatus;
use App\Enums\PetStatus;
use App\Enums\ShelterMemberRole;
use App\Models\CampaignReport;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\WalkSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Публичные страницы без собственного этапа: главная с данными, приюты, «Как помочь», политика.
 */
class PublicPagesTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 06:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_home_works_on_empty_database(): void
    {
        $this->get('/')->assertOk()->assertSee('У каждого здесь есть имя')->assertSee('Что получилось вместе');
    }

    public function test_home_shows_visible_pets_campaigns_slots_and_shelters_only(): void
    {
        $shelter = Shelter::factory()->create(['name' => 'Дом лап']);
        $pending = Shelter::factory()->pending()->create(['name' => 'Тайный приют']);

        Pet::factory()->for($shelter)->status(PetStatus::LookingForHome)->create(['name' => 'Бублик']);
        Pet::factory()->for($shelter)->status(PetStatus::LookingForHome)->hidden()->create(['name' => 'Скрытый']);
        Pet::factory()->for($shelter)->status(PetStatus::Draft)->create(['name' => 'Черновик']);
        Pet::factory()->for($pending)->status(PetStatus::LookingForHome)->create(['name' => 'Непроверенный']);

        DonationCampaign::factory()->for($shelter)->status(CampaignStatus::Active)->create(['title' => 'Операция для Ромашки']);
        DonationCampaign::factory()->for($shelter)->status(CampaignStatus::Active)->create(['title' => 'Скрытый сбор', 'is_hidden' => true]);

        WalkSlot::factory()->for($shelter)->startingAt(Carbon::parse('2026-11-12 10:00', 'Asia/Almaty'))->create();
        WalkSlot::factory()->for($shelter)->startingAt(Carbon::parse('2026-11-25 10:00', 'Asia/Almaty'))->create(['meeting_point' => 'Слишком далеко']);

        $this->get('/')->assertOk()
            ->assertSee('Бублик')->assertDontSee('Скрытый')->assertDontSee('Черновик')->assertDontSee('Непроверенный')
            ->assertSee('Операция для Ромашки')->assertDontSee('Скрытый сбор')
            ->assertSee('Можно погулять на этой неделе')->assertSee('10:00–11:00')
            ->assertSee('Приюты-партнёры')->assertSee('Дом лап')->assertDontSee('Тайный приют');
    }

    public function test_shelters_list_shows_only_approved_and_filters_by_city(): void
    {
        Shelter::factory()->create(['name' => 'Дом лап', 'city' => 'Алматы']);
        Shelter::factory()->create(['name' => 'Мурлыка', 'city' => 'Астана']);
        Shelter::factory()->pending()->create(['name' => 'Котопёс']);
        Shelter::factory()->blocked()->create(['name' => 'Заблокированный']);

        $this->get(route('shelters.index'))->assertOk()
            ->assertSee('Дом лап')->assertSee('Мурлыка')->assertDontSee('Котопёс')->assertDontSee('Заблокированный');

        $this->get(route('shelters.index', ['city' => 'Астана']))->assertOk()->assertSee('Мурлыка')->assertDontSee('Дом лап');
        $this->get(route('shelters.index', ['city' => 'Нет такого']))->assertOk()->assertSee('Дом лап')->assertSee('Мурлыка');
    }

    public function test_shelter_page_shows_contacts_rules_pets_campaigns_slots_and_reports(): void
    {
        $shelter = Shelter::factory()->create([
            'name' => 'Дом лап', 'phone' => '+77012345678', 'address' => 'ул. Жандосова, 186',
            'walk_rules' => "С 18 лет\nПервая прогулка — с куратором",
        ]);
        Pet::factory()->for($shelter)->status(PetStatus::LookingForHome)->create(['name' => 'Бублик']);
        Pet::factory()->for($shelter)->status(PetStatus::LookingForHome)->hidden()->create(['name' => 'Скрытый']);
        Pet::factory()->status(PetStatus::LookingForHome)->create(['name' => 'ЧужойПитомец']);

        $campaign = DonationCampaign::factory()->for($shelter)->status(CampaignStatus::Active)->create(['title' => 'Операция для Ромашки']);
        CampaignReport::factory()->for($campaign, 'campaign')->create(['title' => 'Первичный осмотр и рентген', 'published_at' => now()]);
        WalkSlot::factory()->for($shelter)->startingAt(Carbon::parse('2026-11-12 10:00', 'Asia/Almaty'))->create();

        $this->get(route('shelters.show', $shelter))->assertOk()
            ->assertSee('Дом лап')
            ->assertSee('Проверенный приют')
            ->assertSee('+7 701 234 56 78')
            ->assertSee('Первая прогулка — с куратором')
            ->assertSee('Бублик')->assertDontSee('Скрытый')->assertDontSee('ЧужойПитомец')
            ->assertSee('Операция для Ромашки')
            ->assertSee('Первичный осмотр и рентген')
            ->assertSee('Ближайшие выгулы')
            ->assertSee(route('shelters.donate', $shelter), false);
    }

    public function test_unapproved_shelter_page_is_hidden_from_public(): void
    {
        $pending = Shelter::factory()->pending()->create(['name' => 'Котопёс']);
        $blocked = Shelter::factory()->blocked()->create();

        $this->get(route('shelters.show', $pending))->assertNotFound();
        $this->actingAs($this->volunteer())->get(route('shelters.show', $pending))->assertNotFound();
        $this->get(route('shelters.show', $blocked))->assertNotFound();
    }

    public function test_pending_shelter_page_is_visible_to_its_staff_and_admin_without_indexing(): void
    {
        $pending = Shelter::factory()->pending()->create(['name' => 'Котопёс']);

        $this->actingAs($this->staffOf($pending, ShelterMemberRole::Staff))->get(route('shelters.show', $pending))
            ->assertOk()->assertSee('Приют на проверке')->assertSee('noindex', false)->assertDontSee('Записаться на выгул');

        $this->actingAs($this->admin())->get(route('shelters.show', $pending))->assertOk()->assertSee('Котопёс');
    }

    public function test_help_and_privacy_pages_are_open(): void
    {
        $this->get(route('help'))->assertOk()->assertSee('Частые вопросы')->assertSee('Забрать домой');
        $this->get(route('privacy'))->assertOk()->assertSee('Политика обработки персональных данных');
    }

    public function test_footer_and_header_links_now_resolve(): void
    {
        foreach (['/pets', '/campaigns', '/walks', '/shelters', '/help', '/privacy'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
