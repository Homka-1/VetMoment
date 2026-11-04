<?php

namespace Database\Factories;

use App\Models\CampaignReport;
use App\Models\DonationCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignReport>
 */
class CampaignReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => DonationCampaign::factory(),
            'title' => 'Купили и привезли',
            'body' => 'Спасибо всем, кто помог! Закупили всё по смете, чеки — во вложениях.',
            'spent_amount' => fake()->numberBetween(10, 300) * 1_000 * 100,
            'published_at' => now(),
        ];
    }
}
