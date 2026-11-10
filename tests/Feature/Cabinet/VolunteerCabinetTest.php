<?php

namespace Tests\Feature\Cabinet;

use App\Enums\ApplicationStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\AdoptionApplication;
use App\Models\Donation;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Support\Money;
use App\Support\Navigation;
use App\Support\Plural;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Кабинет волонтёра: обзор, «Мои заявки», профиль и смена пароля.
 */
class VolunteerCabinetTest extends TestCase
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

    public function test_overview_greets_by_first_name_and_shows_next_walk(): void
    {
        $user = $this->volunteer(['name' => 'Айгерим Тестова']);
        $shelter = Shelter::factory()->create(['name' => 'Дом лап']);
        $pet = Pet::factory()->dog()->for($shelter)->create(['name' => 'Бублик']);

        $later = WalkSlot::factory()->for($shelter)->startingAt(Carbon::parse('2026-11-14 15:00', 'Asia/Almaty'))->create();
        $sooner = WalkSlot::factory()->for($shelter)->startingAt(Carbon::parse('2026-11-12 10:00', 'Asia/Almaty'))->create();
        WalkBooking::factory()->for($later, 'slot')->for($user)->status(BookingStatus::Confirmed)->create();
        WalkBooking::factory()->for($sooner, 'slot')->for($user)->for($pet)->status(BookingStatus::Confirmed)->create();

        $this->actingAs($user)->get(route('cabinet.dashboard'))
            ->assertOk()
            ->assertSee('Здравствуйте, Айгерим')
            ->assertSee('Ближайший выгул')
            ->assertSee('10:00')
            ->assertSee('Бублик')
            ->assertSee('Все записи: 2');
    }

    public function test_overview_without_data_shows_empty_states(): void
    {
        $this->actingAs($this->volunteer())->get(route('cabinet.dashboard'))
            ->assertOk()
            ->assertSee('Прогулок пока нет')
            ->assertSee('Заявок пока нет')
            ->assertSee('Выбрать сбор');
    }

    public function test_overview_sums_only_paid_donations_of_this_year(): void
    {
        $user = $this->volunteer();
        Donation::factory()->for($user)->create(['amount' => 500_000]);
        Donation::factory()->for($user)->create(['amount' => 1_000_000]);
        Donation::factory()->for($user)->status(PaymentStatus::Failed)->create(['amount' => 9_900_000]);
        Donation::factory()->for($user)->create(['amount' => 700_000, 'paid_at' => now()->subYear()]);

        $this->actingAs($user)->get(route('cabinet.dashboard'))
            ->assertOk()
            ->assertSee(Money::format(1_500_000))
            ->assertSee(Plural::format(2, ['пожертвование', 'пожертвования', 'пожертвований']))
            ->assertDontSee(Money::format(9_900_000));
    }

    public function test_applications_page_lists_own_applications_with_shelter_answer(): void
    {
        $user = $this->volunteer();
        $pet = Pet::factory()->dog()->create(['name' => 'Рыжик']);
        AdoptionApplication::factory()->for($user)->for($pet)->create(['status' => ApplicationStatus::Rejected, 'shelter_comment' => 'Питомец уже нашёл дом.']);
        AdoptionApplication::factory()->for($user)->create(['status' => ApplicationStatus::New]);
        AdoptionApplication::factory()->create(); // чужая

        $this->actingAs($user)->get(route('cabinet.applications'))->assertOk()->assertSee('Отозвать заявку')->assertDontSee('Питомец уже нашёл дом.');

        $this->get(route('cabinet.applications', ['tab' => 'closed']))
            ->assertOk()
            ->assertSee('Рыжик')
            ->assertSee('Питомец уже нашёл дом.')
            ->assertDontSee('Отозвать заявку');
    }

    public function test_applications_page_requires_login(): void
    {
        $this->get(route('cabinet.applications'))->assertRedirect('/login');
    }

    public function test_profile_is_saved_with_phone_normalization(): void
    {
        $user = $this->volunteer();

        $this->actingAs($user)->patch(route('cabinet.profile.update'), [
            'name' => '  Айгерим Нурлановна  ',
            'phone' => '8 (701) 555-12-34',
            'email' => 'Aigerim@Example.KZ',
        ])->assertRedirect(route('cabinet.profile'))->assertSessionHas('status', 'Профиль сохранён');

        $user->refresh();
        $this->assertSame('Айгерим Нурлановна', $user->name);
        $this->assertSame('+77015551234', $user->phone);
        $this->assertSame('aigerim@example.kz', $user->email);
    }

    public function test_profile_rejects_taken_phone_and_email_but_allows_own(): void
    {
        $other = $this->volunteer(['phone' => '+77011112233', 'email' => 'other@example.kz']);
        $user = $this->volunteer(['phone' => '+77019998877', 'email' => 'me@example.kz']);

        $this->actingAs($user)->patch(route('cabinet.profile.update'), [
            'name' => 'Я', 'phone' => $other->phone, 'email' => $other->email,
        ])->assertSessionHasErrors(['phone', 'email']);

        $this->patch(route('cabinet.profile.update'), [
            'name' => 'Новое имя', 'phone' => $user->phone, 'email' => $user->email,
        ])->assertSessionHasNoErrors();
    }

    public function test_profile_cannot_change_role_or_login(): void
    {
        $user = $this->volunteer(['login' => 'aigerim']);

        $this->actingAs($user)->patch(route('cabinet.profile.update'), [
            'name' => 'Имя', 'phone' => $user->phone, 'email' => $user->email,
            'role' => 'admin', 'login' => 'hacker', 'is_blocked' => true,
        ]);

        $user->refresh();
        $this->assertSame('volunteer', $user->role->value);
        $this->assertSame('aigerim', $user->login);
        $this->assertFalse($user->is_blocked);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password-1']);

        $this->actingAs($user)->put(route('cabinet.profile.password'), [
            'current_password' => 'wrong', 'password' => 'new-password-2', 'password_confirmation' => 'new-password-2',
        ])->assertSessionHasErrors('current_password');

        $this->put(route('cabinet.profile.password'), [
            'current_password' => 'old-password-1', 'password' => 'new-password-2', 'password_confirmation' => 'new-password-2',
        ])->assertRedirect(route('cabinet.profile'))->assertSessionHas('status', 'Пароль изменён');

        $this->assertTrue(Hash::check('new-password-2', $user->fresh()->password));
    }

    public function test_password_must_differ_and_be_confirmed(): void
    {
        $user = User::factory()->create(['password' => 'old-password-1']);

        $this->actingAs($user)->put(route('cabinet.profile.password'), [
            'current_password' => 'old-password-1', 'password' => 'old-password-1', 'password_confirmation' => 'old-password-1',
        ])->assertSessionHasErrors('password');

        $this->put(route('cabinet.profile.password'), [
            'current_password' => 'old-password-1', 'password' => 'new-password-2', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }

    public function test_menu_offers_shelter_registration_only_to_volunteer_without_shelter(): void
    {
        $volunteer = $this->volunteer();
        $this->actingAs($volunteer);
        $this->assertContains('Стать приютом', collect(Navigation::cabinet($volunteer))->pluck('label')->all());

        $shelter = Shelter::factory()->create();
        $staff = $this->staffOf($shelter);
        $this->actingAs($staff);
        $this->assertNotContains('Стать приютом', collect(Navigation::cabinet($staff))->pluck('label')->all());
    }
}
