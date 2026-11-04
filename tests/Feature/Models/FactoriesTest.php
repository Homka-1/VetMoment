<?php

namespace Tests\Feature\Models;

use App\Models\AdoptionApplication;
use App\Models\CampaignReport;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\PetPhoto;
use App\Models\Shelter;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Models\WalkTemplate;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string<Model>}>
     */
    public static function models(): array
    {
        return array_map(fn (string $class) => [$class], [
            'Shelter' => Shelter::class,
            'Pet' => Pet::class,
            'PetPhoto' => PetPhoto::class,
            'AdoptionApplication' => AdoptionApplication::class,
            'DonationCampaign' => DonationCampaign::class,
            'CampaignReport' => CampaignReport::class,
            'Donation' => Donation::class,
            'WalkTemplate' => WalkTemplate::class,
            'WalkSlot' => WalkSlot::class,
            'WalkBooking' => WalkBooking::class,
        ]);
    }

    /**
     * @param  class-string<Model>  $model
     */
    #[DataProvider('models')]
    public function test_factory_creates_records(string $model): void
    {
        $records = $model::factory()->count(3)->create();

        $this->assertSame(3, $model::count());
        $records->each(fn (Model $record) => $this->assertTrue($record->exists));
    }

    public function test_factory_phones_are_valid(): void
    {
        $shelter = Shelter::factory()->withOwner()->create();
        $application = AdoptionApplication::factory()->create();

        $this->assertTrue(Phone::isValid($shelter->phone));
        $this->assertTrue(Phone::isValid($shelter->members->first()->phone));
        $this->assertTrue(Phone::isValid($application->contact_phone));
    }
}
