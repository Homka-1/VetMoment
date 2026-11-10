<?php

namespace Tests\Feature\Walks;

use App\Enums\BookingStatus;
use App\Enums\CancelledBy;
use App\Enums\PetStatus;
use App\Enums\WalkSlotState;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Services\WalkBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Правила записи на выгул W1–W10 (docs/product/data-requirements.md, 7) — WalkBookingService.
 */
class WalkBookingRulesTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    private WalkBookingService $service;

    private Shelter $shelter;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 06:00:00'); // 11:00 в Алматы
        $this->service = app(WalkBookingService::class);
        $this->shelter = Shelter::factory()->create();
        $this->user = $this->volunteer();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function slot(string $in = '+1 day', array $attributes = [], int $minutes = 60): WalkSlot
    {
        return WalkSlot::factory()->for($this->shelter)->startingAt(now()->modify($in), $minutes)->create($attributes);
    }

    private function dog(array $attributes = []): Pet
    {
        return Pet::factory()->dog()->for($this->shelter)->create($attributes);
    }

    private function assertRejected(callable $action, string $field, string $fragment): void
    {
        try {
            $action();
            $this->fail('Ожидалась ошибка валидации');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
            $this->assertStringContainsString($fragment, $e->errors()[$field][0]);
        }
    }

    public function test_booking_creates_pending_and_updates_counter(): void
    {
        $slot = $this->slot();
        $dog = $this->dog();
        $slot->pets()->attach($dog);

        $booking = $this->service->book($this->user, $slot, $dog, 'Беру воду');

        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame($dog->id, $booking->pet_id);
        $this->assertSame('Беру воду', $booking->comment);
        $this->assertSame(1, $slot->fresh()->booked_count);
    }

    public function test_w1_cannot_book_past_or_starting_soon_slot(): void
    {
        $this->assertRejected(fn () => $this->service->book($this->user, $this->slot('-1 hour'), null), 'slot_id', 'уже прошла');

        $soon = $this->slot('+90 minutes');
        $this->assertSame(WalkSlotState::Closed, $soon->state());
        $this->assertRejected(fn () => $this->service->book($this->user, $soon, null), 'slot_id', 'закрыта');

        $this->service->book($this->user, $this->slot('+150 minutes'), null);
        $this->assertSame(1, WalkBooking::count());
    }

    public function test_w2_cannot_book_beyond_horizon(): void
    {
        $this->assertRejected(fn () => $this->service->book($this->user, $this->slot('+15 days'), null), 'slot_id', 'ещё не открыта');
        $this->service->book($this->user, $this->slot('+13 days'), null);
        $this->assertSame(1, WalkBooking::count());
    }

    public function test_w3_cannot_book_cancelled_or_full_slot(): void
    {
        $this->assertRejected(fn () => $this->service->book($this->user, $this->slot('+1 day', ['status' => 'cancelled']), null), 'slot_id', 'отменил');

        $full = $this->slot('+2 days', ['capacity' => 1]);
        $this->service->book($this->volunteer(), $full, null);
        $this->assertRejected(fn () => $this->service->book($this->user, $full, null), 'slot_id', 'Мест больше нет');
        $this->assertSame(1, $full->fresh()->booked_count);
    }

    public function test_cancelled_booking_frees_the_spot(): void
    {
        $slot = $this->slot('+1 day', ['capacity' => 1]);
        $other = $this->service->book($this->volunteer(), $slot, null);
        $this->service->cancelByUser($other);

        $this->service->book($this->user, $slot, null);
        $this->assertSame(1, $slot->fresh()->booked_count);
    }

    public function test_w4_one_dog_once_per_slot(): void
    {
        $slot = $this->slot();
        $dog = $this->dog(['name' => 'Бублик']);
        $slot->pets()->attach($dog);
        $this->service->book($this->volunteer(), $slot, $dog);

        $this->assertRejected(fn () => $this->service->book($this->user, $slot, $dog), 'pet_id', 'Бублик уже гуляет');
        $this->assertSame([$dog->id], $this->service->takenPetIds($slot));

        // «Любая собака» в том же слоте — можно
        $this->service->book($this->user, $slot, null);
        $this->assertSame(2, $slot->fresh()->booked_count);
    }

    public function test_dog_must_be_offered_in_slot(): void
    {
        $slot = $this->slot();
        $slot->pets()->attach($this->dog());
        $stranger = $this->dog(['name' => 'Граф']);

        $this->assertRejected(fn () => $this->service->book($this->user, $slot, $stranger), 'pet_id', 'не гуляет в это время');
    }

    public function test_w5_no_double_booking_and_no_overlaps(): void
    {
        $slot = $this->slot('+1 day');
        $this->service->book($this->user, $slot, null);
        $this->assertRejected(fn () => $this->service->book($this->user, $slot, null), 'slot_id', 'уже записаны');

        // Другой приют, пересекается на 30 минут
        $overlap = WalkSlot::factory()->startingAt(now()->modify('+1 day')->addMinutes(30))->create();
        $this->assertRejected(fn () => $this->service->book($this->user, $overlap, null), 'slot_id', 'уже есть другая прогулка');

        // Встык — можно
        $this->service->book($this->user, $this->slot('+1 day +60 minutes'), null);
        $this->assertSame(2, $this->service->upcomingActive($this->user)->count());
    }

    public function test_w6_active_bookings_limit(): void
    {
        foreach (['+1 day', '+2 days', '+3 days'] as $in) {
            $this->service->book($this->user, $this->slot($in), null);
        }

        $this->assertRejected(fn () => $this->service->book($this->user, $this->slot('+4 days'), null), 'slot_id', 'это максимум');

        // Прошедшие и отменённые записи лимит не занимают
        WalkBooking::query()->first()->update(['status' => BookingStatus::Cancelled]);
        $this->service->book($this->user, $this->slot('+5 days'), null);
        $this->assertSame(3, $this->service->upcomingActive($this->user)->count());
    }

    public function test_w7_user_cancels_not_later_than_three_hours(): void
    {
        $early = $this->service->book($this->user, $this->slot('+1 day'), null);
        $this->service->cancelByUser($early);

        $early->refresh();
        $this->assertSame(BookingStatus::Cancelled, $early->status);
        $this->assertSame(CancelledBy::User, $early->cancelled_by);
        $this->assertSame(0, $early->slot->booked_count);

        $late = WalkBooking::factory()->for($this->user)->create(['walk_slot_id' => $this->slot('+150 minutes')->id]);
        $this->assertFalse($this->service->userCanCancelInTime($late));
        $this->assertRejected(fn () => $this->service->cancelByUser($late), 'booking', 'Позвоните в приют');
    }

    public function test_w8_dog_must_be_ready_for_walks(): void
    {
        $slot = $this->slot();
        $adopted = $this->dog(['name' => 'Тоша']);
        $slot->pets()->attach($adopted);
        $adopted->update(['status' => PetStatus::Adopted]);

        $this->assertRejected(fn () => $this->service->book($this->user, $slot, $adopted), 'pet_id', 'нельзя погулять');
    }

    public function test_w10_no_shows_are_counted_for_ninety_days(): void
    {
        foreach (['-10 days', '-20 days', '-100 days'] as $in) {
            WalkBooking::factory()->for($this->user)->status(BookingStatus::NoShow)
                ->create(['walk_slot_id' => $this->slot($in)->id]);
        }
        $this->assertSame(2, $this->service->recentNoShows($this->user));
        $this->assertFalse($this->service->hasManyNoShows($this->user));

        WalkBooking::factory()->for($this->user)->status(BookingStatus::NoShow)
            ->create(['walk_slot_id' => $this->slot('-3 days')->id]);
        $this->assertTrue($this->service->hasManyNoShows($this->user));

        // Запись всё равно возможна — с предупреждением, решение за приютом
        $this->assertSame(BookingStatus::Pending, $this->service->book($this->user, $this->slot(), null)->status);
    }

    public function test_w9_counter_is_recalculated_from_active_bookings(): void
    {
        $slot = $this->slot('+1 day', ['capacity' => 2, 'booked_count' => 2]); // рассинхрон счётчика

        $this->service->book($this->user, $slot, null);

        $this->assertSame(1, $slot->fresh()->booked_count, 'счётчик берётся из активных записей под блокировкой');
    }

    public function test_shelter_transitions(): void
    {
        $slot = $this->slot();
        $booking = $this->service->book($this->user, $slot, null);

        $this->assertSame([BookingStatus::Confirmed, BookingStatus::Rejected], $this->service->nextStatuses($booking));
        $this->service->process($booking, BookingStatus::Confirmed);
        $this->assertNotNull($booking->fresh()->confirmed_at);

        // До начала нельзя отметить «состоялся»
        $this->assertRejected(fn () => $this->service->process($booking, BookingStatus::Completed), 'status', 'другом статусе');

        Carbon::setTestNow(now()->addDays(2));
        $this->assertSame([BookingStatus::Completed, BookingStatus::NoShow], $this->service->nextStatuses($booking->fresh()));
        $this->service->process($booking, BookingStatus::NoShow);
        $this->assertSame(BookingStatus::NoShow, $booking->fresh()->status);
        $this->assertSame(0, $slot->fresh()->booked_count);
    }

    public function test_rejection_stores_reason_and_frees_spot(): void
    {
        $slot = $this->slot('+1 day', ['capacity' => 1]);
        $booking = $this->service->book($this->user, $slot, null);

        $this->service->process($booking, BookingStatus::Rejected, 'Собака у ветеринара');

        $this->assertSame('Собака у ветеринара', $booking->fresh()->shelter_comment);
        $this->assertSame(0, $slot->fresh()->booked_count);
    }
}
