<?php

namespace Database\Factories;

use App\Models\Pet;
use App\Models\PetPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PetPhoto>
 */
class PetPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pet_id' => Pet::factory(),
            'path' => 'pets/'.fake()->uuid().'.jpg',
            'sort' => 0,
            'is_cover' => false,
        ];
    }

    public function cover(): static
    {
        return $this->state(['is_cover' => true]);
    }
}
