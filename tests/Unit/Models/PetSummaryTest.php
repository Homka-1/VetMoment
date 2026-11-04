<?php

namespace Tests\Unit\Models;

use App\Enums\Sex;
use App\Enums\Species;
use App\Models\Pet;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PetSummaryTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_summary_contains_species_age_and_sex(): void
    {
        Carbon::setTestNow('2026-11-04');
        $pet = new Pet(['species' => Species::Dog, 'sex' => Sex::Male, 'birth_date' => '2024-09-01']);

        $this->assertSame("Собака, 2\u{00A0}года, мальчик", $pet->summary());
    }

    public function test_unknown_age_and_sex_are_skipped(): void
    {
        $pet = new Pet(['species' => Species::Cat, 'sex' => Sex::Unknown, 'birth_date' => null]);

        $this->assertNull($pet->ageLabel());
        $this->assertSame('Кошка', $pet->summary());
    }

    public function test_age_label_for_kitten(): void
    {
        Carbon::setTestNow('2026-11-04');
        $pet = new Pet(['species' => Species::Cat, 'sex' => Sex::Female, 'birth_date' => '2026-07-01']);

        $this->assertSame("4\u{00A0}месяца", $pet->ageLabel());
    }
}
