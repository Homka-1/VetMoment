<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\CancelledBy;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика не пересчитывает walk_slots.booked_count — после создания вызовите
 * WalkSlot::recalculateBookedCount() (так делает сидер).
 *
 * @extends Factory<WalkBooking>
 */
class WalkBookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'walk_slot_id' => WalkSlot::factory(),
            'user_id' => User::factory(),
            'pet_id' => null,
            'status' => BookingStatus::Pending,
            'comment' => null,
        ];
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'confirmed_at' => in_array($status, [BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::NoShow], true) ? now() : null,
            'cancelled_at' => $status === BookingStatus::Cancelled ? now() : null,
            'cancelled_by' => $status === BookingStatus::Cancelled ? CancelledBy::User : null,
        ]);
    }
}
