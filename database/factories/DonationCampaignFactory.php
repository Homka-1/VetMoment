<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\DonationCampaign;
use App\Models\Shelter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonationCampaign>
 */
class DonationCampaignFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shelter_id' => Shelter::factory(),
            'pet_id' => null,
            'title' => fake()->randomElement(['Корм на зиму', 'Вакцины на осень', 'Новые вольеры', 'Тёплая будка', 'Лекарства']),
            'short_description' => 'Собираем на самое необходимое — отчёт с чеками опубликуем после сбора.',
            'description' => fake()->realText(400),
            'goal_amount' => fake()->numberBetween(50, 1000) * 1_000 * 100, // 50 000 – 1 000 000 ₸ в тиынах
            'raised_amount' => 0,
            'donations_count' => 0,
            'starts_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'ends_at' => now()->addDays(fake()->numberBetween(3, 60)),
            'allow_overfunding' => false,
            'status' => CampaignStatus::Active,
            'is_hidden' => false,
        ];
    }

    public function status(CampaignStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
