<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\AdoptionApplication;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdoptionApplication>
 */
class AdoptionApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pet_id' => Pet::factory(),
            'user_id' => User::factory(),
            'type' => ApplicationType::Adoption,
            'contact_name' => fake()->firstName(),
            'contact_phone' => '+7707'.fake()->numerify('#######'),
            'city' => 'Алматы',
            'experience' => 'Раньше была собака, прожила с нами 12 лет.',
            'living_conditions' => 'Своя квартира, других животных нет.',
            'message' => null,
            'status' => ApplicationStatus::New,
        ];
    }

    public function guardianship(): static
    {
        return $this->state(['type' => ApplicationType::Guardianship, 'living_conditions' => null]);
    }

    public function status(ApplicationStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'processed_at' => in_array($status, [ApplicationStatus::New, ApplicationStatus::InReview], true) ? null : now(),
        ]);
    }
}
