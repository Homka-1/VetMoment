<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Shelter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => null,
            'shelter_id' => Shelter::factory(),
            'user_id' => null,
            'donor_name' => fake()->firstName(),
            'donor_email' => fake()->safeEmail(),
            'is_anonymous' => false,
            'amount' => fake()->randomElement([1_000, 2_000, 5_000, 10_000]) * 100, // тиын
            'comment' => null,
            'status' => PaymentStatus::Succeeded,
            'provider' => 'fake',
            'provider_payment_id' => 'fake_'.fake()->unique()->uuid(),
            'paid_at' => now(),
        ];
    }

    /** Пожертвование на сбор — приют берётся из сбора. */
    public function forCampaign(DonationCampaign $campaign): static
    {
        return $this->state(['campaign_id' => $campaign->id, 'shelter_id' => $campaign->shelter_id]);
    }

    public function status(PaymentStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'paid_at' => $status === PaymentStatus::Succeeded ? now() : null,
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(['is_anonymous' => true]);
    }
}
