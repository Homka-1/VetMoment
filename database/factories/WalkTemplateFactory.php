<?php

namespace Database\Factories;

use App\Models\Shelter;
use App\Models\WalkTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalkTemplate>
 */
class WalkTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shelter_id' => Shelter::factory(),
            'weekdays' => WalkTemplate::maskFromDays([6, 7]),
            'start_time' => '10:00',
            'duration_minutes' => 60,
            'capacity' => 4,
            'meeting_point' => 'У ворот приюта',
            'valid_from' => today(),
            'valid_until' => null,
        ];
    }
}
