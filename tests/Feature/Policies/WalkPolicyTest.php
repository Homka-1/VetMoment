<?php

namespace Tests\Feature\Policies;

use App\Enums\BookingStatus;
use App\Enums\ShelterMemberRole;
use App\Models\Shelter;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Матрица прав на выгул — docs/product/roles.md, 2.4.
 */
class WalkPolicyTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_schedule_of_approved_shelter_is_public(): void
    {
        $slot = WalkSlot::factory()->create();
        $hidden = WalkSlot::factory()->for(Shelter::factory()->pending())->create();

        $this->assertTrue(Gate::forUser(null)->allows('view', $slot));
        $this->assertFalse(Gate::forUser(null)->allows('view', $hidden));
        $this->assertTrue(Gate::forUser($this->staffOf($hidden->shelter))->allows('view', $hidden));
    }

    public function test_slots_are_created_only_by_approved_shelter_or_admin(): void
    {
        $this->assertTrue(Gate::forUser($this->staffOf(Shelter::factory()->create()))->allows('create', WalkSlot::class));
        $this->assertFalse(Gate::forUser($this->staffOf(Shelter::factory()->pending()->create()))->allows('create', WalkSlot::class));
        $this->assertTrue(Gate::forUser($this->admin())->allows('create', WalkSlot::class));
        $this->assertFalse(Gate::forUser($this->volunteer())->allows('create', WalkSlot::class));
    }

    public function test_slot_management_is_limited_to_own_shelter(): void
    {
        $slot = WalkSlot::factory()->create();
        $other = $this->staffOf(Shelter::factory()->create());

        foreach (['update', 'delete', 'viewBookings'] as $ability) {
            $this->assertTrue(Gate::forUser($this->staffOf($slot->shelter, ShelterMemberRole::Staff))->allows($ability, $slot), $ability);
            $this->assertTrue(Gate::forUser($this->admin())->allows($ability, $slot), $ability);
            $this->assertFalse(Gate::forUser($other)->allows($ability, $slot), $ability);
            $this->assertFalse(Gate::forUser($this->volunteer())->allows($ability, $slot), $ability);
        }
    }

    public function test_who_can_book_a_slot(): void
    {
        $slot = WalkSlot::factory()->create();

        $this->assertTrue(Gate::forUser($this->volunteer())->allows('book', $slot));
        $this->assertTrue(Gate::forUser($this->staffOf(Shelter::factory()->create()))->allows('book', $slot), 'сотрудник другого приюта');
        $this->assertFalse(Gate::forUser($this->staffOf($slot->shelter))->allows('book', $slot), 'не в свой приют');
        $this->assertFalse(Gate::forUser($this->admin())->allows('book', $slot), 'админ не гуляет');
        $this->assertFalse(Gate::forUser(null)->allows('book', $slot), 'гость → вход');
        $this->assertFalse(Gate::forUser($this->volunteer())->allows('book', WalkSlot::factory()->cancelled()->create()));
        $this->assertFalse(Gate::forUser($this->volunteer())->allows(
            'book', WalkSlot::factory()->for(Shelter::factory()->blocked())->create(),
        ));
    }

    public function test_booking_is_visible_to_its_author_shelter_and_admin(): void
    {
        $booking = WalkBooking::factory()->create();

        $this->assertTrue(Gate::forUser($booking->user)->allows('view', $booking));
        $this->assertTrue(Gate::forUser($this->staffOf($booking->slot->shelter))->allows('view', $booking));
        $this->assertTrue(Gate::forUser($this->admin())->allows('view', $booking));
        $this->assertFalse(Gate::forUser($this->volunteer())->allows('view', $booking));
    }

    public function test_volunteer_cancels_only_own_active_booking(): void
    {
        $booking = WalkBooking::factory()->create();
        $done = WalkBooking::factory()->status(BookingStatus::Completed)->create();

        $this->assertTrue(Gate::forUser($booking->user)->allows('cancel', $booking));
        $this->assertFalse(Gate::forUser($this->volunteer())->allows('cancel', $booking));
        $this->assertFalse(Gate::forUser($done->user)->allows('cancel', $done));
        $this->assertFalse(Gate::forUser($this->staffOf($booking->slot->shelter))->allows('cancel', $booking));
    }

    public function test_shelter_manages_bookings_of_its_slots(): void
    {
        $booking = WalkBooking::factory()->create();

        $this->assertTrue(Gate::forUser($this->staffOf($booking->slot->shelter))->allows('manage', $booking));
        $this->assertTrue(Gate::forUser($this->admin())->allows('manage', $booking));
        $this->assertFalse(Gate::forUser($booking->user)->allows('manage', $booking));
        $this->assertFalse(Gate::forUser($this->staffOf(Shelter::factory()->create()))->allows('manage', $booking));
    }
}
