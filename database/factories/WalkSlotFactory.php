<?php

namespace Database\Factories;

use App\Enums\WalkSlotStatus;
use App\Models\Shelter;
use App\Models\WalkSlot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<WalkSlot>
 */
class WalkSlotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = Carbon::now(config('app.display_timezone'))
            ->addDays(fake()->numberBetween(1, 7))
            ->setTime(fake()->randomElement([10, 12, 15, 17]), 0)
            ->utc();

        return [
            'shelter_id' => Shelter::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'capacity' => 4,
            'booked_count' => 0,
            'meeting_point' => 'У ворот приюта',
            'status' => WalkSlotStatus::Open,
        ];
    }

    public function startingAt(Carbon $startsAt, int $minutes = 60): static
    {
        return $this->state([
            'starts_at' => $startsAt->copy()->utc(),
            'ends_at' => $startsAt->copy()->utc()->addMinutes($minutes),
        ]);
    }

    public function past(): static
    {
        return $this->startingAt(now()->subDays(2));
    }

    public function cancelled(): static
    {
        return $this->state(['status' => WalkSlotStatus::Cancelled]);
    }
}
