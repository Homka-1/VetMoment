<?php

namespace Tests\Feature\Walks;

use App\Enums\BookingStatus;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Запись на выгул глазами волонтёра: календарь, подтверждение, запись, отмена, «Мои выгулы», .ics.
 */
class WalkPagesTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    private Shelter $shelter;

    private WalkSlot $slot;

    private Pet $dog;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 06:00:00'); // вторник, 11:00 в Алматы
        $this->shelter = Shelter::factory()->create(['name' => 'Дом лап', 'walk_rules' => 'Возьмите удостоверение личности.']);
        $this->dog = Pet::factory()->dog()->for($this->shelter)->create(['name' => 'Бублик']);
        $this->slot = WalkSlot::factory()->for($this->shelter)
            ->startingAt(Carbon::parse('2026-11-14 10:00', 'Asia/Almaty'))->create(['capacity' => 4]);
        $this->slot->pets()->attach($this->dog);
        $this->user = $this->volunteer();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_calendar_shows_week_of_shelter_slots(): void
    {
        WalkSlot::factory()->startingAt(now()->addDay())->create(); // другой приют
        Shelter::factory()->pending()->create(['name' => 'На проверке']);

        $this->get('/walks')->assertOk()
            ->assertSee('Дом лап')->assertDontSee('На проверке')
            ->assertSee('9–15 ноября')
            ->assertSee('10:00–11:00')
            ->assertSee("Осталось 4\u{00A0}места")
            ->assertSee('Выгула нет');
    }

    public function test_calendar_filters_by_dog_and_preselects_shelter(): void
    {
        $other = WalkSlot::factory()->for($this->shelter)->startingAt(Carbon::parse('2026-11-12 17:00', 'Asia/Almaty'))->create();
        $other->pets()->attach(Pet::factory()->dog()->for($this->shelter)->create());

        $this->get('/walks?pet='.$this->dog->slug)->assertOk()
            ->assertSee('10:00–11:00')
            ->assertDontSee('17:00–18:00');
    }

    public function test_week_navigation_is_limited_to_horizon(): void
    {
        $this->get('/walks?week=2026-11-30')->assertOk()->assertSee('23–29 ноября'); // дальше горизонта — последняя неделя
        $this->get('/walks?week=2026-10-01')->assertOk()->assertSee('9–15 ноября');
    }

    public function test_continue_redirects_to_confirmation(): void
    {
        $this->get('/walks/continue?slot_id='.$this->slot->id.'&pet='.$this->dog->slug)
            ->assertRedirect(route('walks.slots.show', [$this->slot, 'pet' => $this->dog->slug]));

        $this->from('/walks')->get('/walks/continue')->assertRedirect('/walks')->assertSessionHasErrors('slot_id');
    }

    public function test_confirmation_requires_login(): void
    {
        $this->get(route('walks.slots.show', $this->slot))->assertRedirect('/login');
    }

    public function test_confirmation_page_shows_dogs_rules_and_preselect(): void
    {
        $this->actingAs($this->user)->get(route('walks.slots.show', [$this->slot, 'pet' => $this->dog->slug]))
            ->assertOk()
            ->assertSee('С кем погуляете')
            ->assertSee('Бублик')
            ->assertSee('Возьмите удостоверение личности.')
            ->assertSee("не позже чем за 3\u{00A0}часа")
            ->assertSee('value="'.$this->dog->id.'" checked', false);
    }

    public function test_booking_flow_creates_pending_booking(): void
    {
        $response = $this->actingAs($this->user)->post(route('walks.book', $this->slot), [
            'pet_id' => $this->dog->id,
            'comment' => 'Беру воду',
            'adult' => '1',
            'rules' => '1',
        ]);

        $booking = WalkBooking::sole();
        $response->assertRedirect(route('walks.bookings.show', $booking))->assertSessionHas('status', 'Вы записаны на выгул');
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(1, $this->slot->fresh()->booked_count);

        $this->get(route('walks.bookings.show', $booking))->assertOk()
            ->assertSee('Вы записаны на выгул')->assertSee('Ждёт подтверждения')->assertSee('Отменить запись');

        // Повторно открыть слот → сразу на свою запись
        $this->get(route('walks.slots.show', $this->slot))->assertRedirect(route('walks.bookings.show', $booking));
    }

    public function test_booking_requires_age_and_rules_confirmation(): void
    {
        $this->actingAs($this->user)->post(route('walks.book', $this->slot), [])
            ->assertSessionHasErrors(['adult', 'rules']);
        $this->assertSame(0, WalkBooking::count());
    }

    public function test_business_rule_errors_return_to_form(): void
    {
        $this->slot->update(['capacity' => 1]);
        WalkBooking::factory()->create(['walk_slot_id' => $this->slot->id]);

        $this->actingAs($this->user)->from(route('walks.slots.show', $this->slot))
            ->post(route('walks.book', $this->slot), ['adult' => '1', 'rules' => '1'])
            ->assertRedirect(route('walks.slots.show', $this->slot))
            ->assertSessionHasErrors('slot_id');
    }

    public function test_admin_and_own_staff_cannot_book(): void
    {
        $data = ['adult' => '1', 'rules' => '1'];
        $this->actingAs($this->admin())->post(route('walks.book', $this->slot), $data)->assertForbidden();
        $this->actingAs($this->staffOf($this->shelter))->post(route('walks.book', $this->slot), $data)->assertForbidden();

        $this->actingAs($this->staffOf($this->shelter))->get(route('walks.slots.show', $this->slot))
            ->assertOk()->assertSee('Это прогулка вашего приюта');
    }

    public function test_user_cancels_own_booking(): void
    {
        $booking = WalkBooking::factory()->for($this->user)->create(['walk_slot_id' => $this->slot->id]);
        $this->slot->recalculateBookedCount();

        $this->actingAs($this->volunteer())->post(route('walks.bookings.cancel', $booking))->assertForbidden();
        $this->actingAs($this->volunteer())->get(route('walks.bookings.show', $booking))->assertForbidden();

        $this->actingAs($this->user)->post(route('walks.bookings.cancel', $booking))
            ->assertRedirect(route('walks.bookings.show', $booking))->assertSessionHas('status', 'Запись отменена');
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertSame(0, $this->slot->fresh()->booked_count);
    }

    public function test_late_cancellation_is_refused_with_phone_hint(): void
    {
        $booking = WalkBooking::factory()->for($this->user)->create(['walk_slot_id' => $this->slot->id]);
        Carbon::setTestNow('2026-11-14 03:00:00'); // 08:00 в Алматы — до начала 2 часа

        $this->actingAs($this->user)->get(route('walks.bookings.show', $booking))
            ->assertOk()->assertSee('Отменить запись на сайте уже нельзя')->assertDontSee('data-modal-open="cancel-booking"', false);

        $this->post(route('walks.bookings.cancel', $booking))->assertSessionHasErrors('booking');
        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_calendar_file(): void
    {
        $booking = WalkBooking::factory()->for($this->user)->create(['walk_slot_id' => $this->slot->id, 'pet_id' => $this->dog->id]);

        $this->actingAs($this->user)->get(route('walks.bookings.calendar', $booking))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertSee('DTSTART:20261114T050000Z')
            ->assertSee('SUMMARY:Выгул в приюте «Дом лап» — Бублик', false);
    }

    public function test_my_walks_lists_upcoming_and_past(): void
    {
        WalkBooking::factory()->for($this->user)->create(['walk_slot_id' => $this->slot->id, 'pet_id' => $this->dog->id]);
        $past = WalkSlot::factory()->for($this->shelter)->startingAt(now()->subDays(3))->create();
        WalkBooking::factory()->for($this->user)->status(BookingStatus::Completed)->create(['walk_slot_id' => $past->id]);
        WalkBooking::factory()->create(['walk_slot_id' => $this->slot->id]); // чужая

        $this->actingAs($this->user)->get('/cabinet/walks')->assertOk()
            ->assertSee('Бублик')->assertSee('Активных записей: 1 из 3')->assertDontSee('Состоялся');

        $this->get('/cabinet/walks?tab=past')->assertOk()->assertSee('Состоялся');
    }

    public function test_pet_card_links_to_walks(): void
    {
        $this->get(route('pets.show', $this->dog))->assertOk()
            ->assertSee(route('walks.slots.show', [$this->slot, 'pet' => $this->dog->slug]), false)
            ->assertSee(route('walks.index', ['pet' => $this->dog->slug]), false);
    }
}
