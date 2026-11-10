<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\BookingStatus;
use App\Enums\CampaignStatus;
use App\Enums\CancelledBy;
use App\Enums\PaymentStatus;
use App\Enums\PetStatus;
use App\Enums\ShelterStatus;
use App\Models\AdoptionApplication;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Support\Money;
use App\Support\Plural;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Кабинет администратора: статистика, очередь модерации, анкеты, сборы, заявки, пользователи.
 */
class AdminPanelTest extends TestCase
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

    public function test_only_admin_opens_admin_pages(): void
    {
        $routes = ['admin.dashboard', 'admin.shelters.index', 'admin.pets.index', 'admin.campaigns.index', 'admin.applications.index', 'admin.users.index'];

        foreach ($routes as $name) {
            $this->get(route($name))->assertRedirect('/login');
        }

        $this->actingAs($this->volunteer());
        foreach ($routes as $name) {
            $this->get(route($name))->assertForbidden();
        }

        $this->actingAs($this->staffOf(Shelter::factory()->create()));
        foreach ($routes as $name) {
            $this->get(route($name))->assertForbidden();
        }

        $this->actingAs($this->admin());
        foreach ($routes as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    public function test_hide_and_block_actions_are_forbidden_for_non_admins(): void
    {
        $pet = Pet::factory()->create();
        $campaign = DonationCampaign::factory()->create();
        $user = $this->volunteer();

        $this->actingAs($this->staffOf($pet->shelter));
        $this->post(route('admin.pets.hide', $pet))->assertForbidden();
        $this->post(route('admin.campaigns.hide', $campaign))->assertForbidden();
        $this->post(route('admin.users.block', $user))->assertForbidden();

        $this->assertFalse($pet->fresh()->is_hidden);
        $this->assertFalse($campaign->fresh()->is_hidden);
        $this->assertFalse($user->fresh()->is_blocked);
    }

    public function test_statistics_for_selected_month(): void
    {
        $shelter = Shelter::factory()->create();
        $slotThisMonth = WalkSlot::factory()->for($shelter)->startingAt(Carbon::parse('2026-11-03 10:00', 'Asia/Almaty'))->create();
        $slotOld = WalkSlot::factory()->for($shelter)->startingAt(Carbon::parse('2026-10-03 10:00', 'Asia/Almaty'))->create();

        foreach (range(1, 3) as $i) {
            WalkBooking::factory()->for($slotThisMonth, 'slot')->status(BookingStatus::Completed)->create();
        }
        WalkBooking::factory()->for($slotThisMonth, 'slot')->status(BookingStatus::NoShow)->create();
        WalkBooking::factory()->for($slotOld, 'slot')->status(BookingStatus::Completed)->create();

        Pet::factory()->for($shelter)->status(PetStatus::Adopted)->create(['adopted_at' => now()]);
        Pet::factory()->for($shelter)->status(PetStatus::Adopted)->create(['adopted_at' => now()->subMonth()]);
        Donation::factory()->create(['amount' => 250_000_000, 'paid_at' => now()]);
        Donation::factory()->status(PaymentStatus::Failed)->create(['amount' => 999_000_000]);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(Money::compact(250_000_000))
            ->assertSee('25%')
            ->assertSee('1 из 4')
            ->assertDontSee(Money::compact(999_000_000));

        $this->get(route('admin.dashboard', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('Октябрь 2026')
            ->assertSee(Plural::format(0, ['пожертвование', 'пожертвования', 'пожертвований']));
    }

    public function test_statistics_ignore_unknown_month(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard', ['month' => 'мусор']))->assertOk()->assertSee('Ноябрь 2026');
    }

    public function test_shelter_queue_tabs_and_actions(): void
    {
        $pending = Shelter::factory()->pending()->create(['name' => 'Лапа помощи', 'bin_iin' => '120440012345']);
        $approved = Shelter::factory()->create(['name' => 'Дом лап']);
        $owner = $this->staffOf($pending);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.shelters.index'))
            ->assertOk()
            ->assertSee('Лапа помощи')
            ->assertSee('120440012345')
            ->assertSee($owner->name)
            ->assertSee('Одобрить')
            ->assertDontSee('Дом лап');

        $this->get(route('admin.shelters.index', ['status' => 'approved']))
            ->assertOk()->assertSee('Дом лап')->assertSee('Заблокировать')->assertDontSee('Лапа помощи');

        $this->post(route('admin.shelters.approve', $pending))->assertRedirect();
        $this->assertSame(ShelterStatus::Approved, $pending->fresh()->status);

        $this->post(route('admin.shelters.block', $approved), ['reason' => 'Жалобы волонтёров'])->assertRedirect();
        $this->get(route('admin.shelters.index', ['status' => 'blocked']))
            ->assertOk()->assertSee('Жалобы волонтёров')->assertSee('Разблокировать');
    }

    public function test_empty_queue_has_a_message(): void
    {
        $this->actingAs($this->admin())->get(route('admin.shelters.index'))->assertOk()->assertSee('Очередь пуста');
    }

    public function test_hiding_pet_removes_it_from_the_site_and_restore_returns_it(): void
    {
        $pet = Pet::factory()->dog()->status(PetStatus::LookingForHome)->create(['name' => 'Бублик']);
        $admin = $this->admin();

        $this->get(route('pets.show', $pet))->assertOk();

        $this->actingAs($admin)->post(route('admin.pets.hide', $pet))->assertRedirect();
        $this->assertTrue($pet->fresh()->is_hidden);

        $this->actingAs($this->volunteer())->get(route('pets.show', $pet))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.pets.index', ['tab' => 'hidden']))->assertOk()->assertSee('Бублик')->assertSee('Скрыта с сайта');

        $this->post(route('admin.pets.restore', $pet))->assertRedirect();
        $this->assertFalse($pet->fresh()->is_hidden);
        $this->actingAs($this->volunteer())->get(route('pets.show', $pet))->assertOk();
    }

    public function test_pets_list_hides_drafts_and_searches_by_name(): void
    {
        Pet::factory()->status(PetStatus::LookingForHome)->create(['name' => 'Бублик']);
        Pet::factory()->status(PetStatus::LookingForHome)->create(['name' => 'Мята']);
        Pet::factory()->status(PetStatus::Draft)->create(['name' => 'Черновичок']);

        $this->actingAs($this->admin())->get(route('admin.pets.index'))
            ->assertOk()->assertSee('Бублик')->assertDontSee('Черновичок');

        $this->get(route('admin.pets.index', ['q' => 'мята']))->assertOk()->assertSee('Мята')->assertDontSee('Бублик');
    }

    public function test_hiding_campaign_closes_public_page(): void
    {
        $campaign = DonationCampaign::factory()->status(CampaignStatus::Active)->create(['title' => 'Операция для Ромашки']);

        $this->get(route('campaigns.show', $campaign))->assertOk();

        $this->actingAs($this->admin())->post(route('admin.campaigns.hide', $campaign))->assertRedirect();
        $this->actingAs($this->volunteer())->get(route('campaigns.show', $campaign))->assertNotFound();

        $this->actingAs($this->admin())->get(route('admin.campaigns.index', ['tab' => 'hidden']))
            ->assertOk()->assertSee('Операция для Ромашки')->assertSee('Скрыт с сайта');

        $this->post(route('admin.campaigns.restore', $campaign))->assertRedirect();
        $this->assertFalse($campaign->fresh()->is_hidden);
    }

    public function test_applications_are_read_only_and_filterable(): void
    {
        AdoptionApplication::factory()->create(['contact_name' => 'Новая Заявка', 'status' => ApplicationStatus::New]);
        AdoptionApplication::factory()->create(['contact_name' => 'Закрытая Заявка', 'status' => ApplicationStatus::Rejected]);

        $this->actingAs($this->admin())->get(route('admin.applications.index'))
            ->assertOk()->assertSee('Новая Заявка')->assertSee('Закрытая Заявка');

        $this->get(route('admin.applications.index', ['status' => 'rejected']))
            ->assertOk()->assertSee('Закрытая Заявка')->assertDontSee('Новая Заявка')->assertDontSee('Взять в работу');
    }

    public function test_users_search_and_role_filter(): void
    {
        $this->volunteer(['name' => 'Марат Тестовый', 'login' => 'marat']);
        $this->volunteer(['name' => 'Ольга Проверочная', 'login' => 'olga']);
        $this->staffOf(Shelter::factory()->create(['name' => 'Дом лап']));

        $this->actingAs($this->admin())->get(route('admin.users.index', ['q' => 'марат']))
            ->assertOk()->assertSee('Марат Тестовый')->assertDontSee('Ольга Проверочная');

        $this->get(route('admin.users.index', ['q' => 'olga']))->assertOk()->assertSee('Ольга Проверочная')->assertDontSee('Марат Тестовый');
        $this->get(route('admin.users.index', ['role' => 'shelter_staff']))->assertOk()->assertSee('Дом лап')->assertDontSee('Марат Тестовый');
    }

    public function test_blocking_user_cancels_future_bookings_and_frees_places(): void
    {
        $shelter = Shelter::factory()->create();
        $user = $this->volunteer(['login' => 'marat']);
        $future = WalkSlot::factory()->for($shelter)->startingAt(Carbon::parse('2026-11-14 10:00', 'Asia/Almaty'))->create();
        $past = WalkSlot::factory()->for($shelter)->startingAt(Carbon::parse('2026-11-05 10:00', 'Asia/Almaty'))->create();

        $active = WalkBooking::factory()->for($future, 'slot')->for($user)->status(BookingStatus::Confirmed)->create();
        $old = WalkBooking::factory()->for($past, 'slot')->for($user)->status(BookingStatus::Completed)->create();
        $future->recalculateBookedCount();
        $this->assertSame(1, $future->fresh()->booked_count);

        $this->actingAs($this->admin())->post(route('admin.users.block', $user))
            ->assertRedirect()->assertSessionHas('status', fn (string $m) => str_contains($m, 'отменено '.Plural::format(1, ['запись', 'записи', 'записей'])));

        $this->assertTrue($user->fresh()->is_blocked);
        $this->assertSame(BookingStatus::Cancelled, $active->fresh()->status);
        $this->assertSame(CancelledBy::System, $active->fresh()->cancelled_by);
        $this->assertSame(BookingStatus::Completed, $old->fresh()->status);
        $this->assertSame(0, $future->fresh()->booked_count);
    }

    public function test_blocked_user_can_be_unblocked_and_cannot_log_in_meanwhile(): void
    {
        $user = $this->volunteer(['login' => 'marat']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.block', $user));
        $this->assertTrue($user->fresh()->is_blocked);

        $this->get(route('admin.users.index', ['blocked' => 1]))->assertOk()->assertSee('Заблокирован')->assertSee('Разблокировать');

        $this->post(route('admin.users.unblock', $user))->assertRedirect();
        $this->assertFalse($user->fresh()->is_blocked);
    }

    public function test_admin_cannot_block_admins_or_already_blocked_users(): void
    {
        $admin = $this->admin();
        $other = User::factory()->admin()->create();
        $blocked = User::factory()->blocked()->create();

        $this->actingAs($admin);
        $this->post(route('admin.users.block', $admin))->assertForbidden();
        $this->post(route('admin.users.block', $other))->assertForbidden();
        $this->post(route('admin.users.block', $blocked))->assertForbidden();
        $this->post(route('admin.users.unblock', $this->volunteer()))->assertForbidden();
    }
}
