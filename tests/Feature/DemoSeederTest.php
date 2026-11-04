<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\ShelterStatus;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\PetPhoto;
use App\Models\Shelter;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed();
    }

    public function test_demo_accounts_exist_with_roles(): void
    {
        $expected = [
            'admin' => Role::Admin,
            'volunteer' => Role::Volunteer,
            'shelter' => Role::ShelterStaff,
            'kotopes' => Role::ShelterStaff,
        ];

        foreach ($expected as $login => $role) {
            $user = User::where('login', $login)->firstOrFail();
            $this->assertSame($role, $user->role, $login);
            $this->assertTrue(Hash::check(DemoSeeder::PASSWORD, $user->password), $login);
        }

        $this->assertSame('Дом лап', User::where('login', 'shelter')->first()->shelter()->name);
    }

    public function test_shelters_and_pets_match_mockups(): void
    {
        $this->assertSame(4, Shelter::approved()->count());
        $this->assertSame(ShelterStatus::Pending, Shelter::where('name', 'Котопёс')->first()->status);
        $this->assertEqualsCanonicalizing(['Алматы', 'Астана', 'Шымкент', 'Караганда'], Shelter::approved()->pluck('city')->all());

        $this->assertGreaterThanOrEqual(25, Pet::inCatalog()->count());
        $this->assertTrue(Pet::visible()->where('name', 'Бублик')->exists());
        $this->assertFalse(Pet::visible()->where('name', 'Кекс')->exists()); // приют на модерации

        // У каждого питомца ровно одна обложка, и файлы фото есть на диске
        Pet::with('photos')->get()->each(function (Pet $pet): void {
            $this->assertSame(1, $pet->photos->where('is_cover', true)->count(), $pet->name);
        });
        PetPhoto::pluck('path')->unique()->each(fn (string $path) => Storage::disk('public')->assertExists($path));
    }

    public function test_campaign_counters_match_donations(): void
    {
        DonationCampaign::all()->each(function (DonationCampaign $campaign): void {
            $succeeded = $campaign->donations()->where('status', PaymentStatus::Succeeded);

            $this->assertSame((int) $succeeded->sum('amount'), $campaign->raised_amount, $campaign->title);
            $this->assertSame($succeeded->count(), $campaign->donations_count, $campaign->title);
        });

        $romashka = DonationCampaign::where('title', 'Операция на лапе для Ромашки')->first();
        $this->assertSame(412_500 * 100, $romashka->raised_amount);
        $this->assertSame(84, $romashka->donations_count);
        $this->assertTrue($romashka->pet->is(Pet::where('name', 'Ромашка')->first()));
        $this->assertSame(2, DonationCampaign::has('reports')->count());
    }

    public function test_walk_data_follows_booking_rules(): void
    {
        $this->assertGreaterThan(5, WalkSlot::upcoming()->count());

        WalkSlot::with('bookings')->get()->each(function (WalkSlot $slot): void {
            $active = $slot->bookings->filter(fn (WalkBooking $b) => $b->status->isActive());

            $this->assertSame($active->count(), $slot->booked_count);
            $this->assertLessThanOrEqual($slot->capacity, $slot->booked_count);
            $this->assertSame($slot->bookings->count(), $slot->bookings->unique('user_id')->count(), 'W5');
            $this->assertSame($active->whereNotNull('pet_id')->count(), $active->whereNotNull('pet_id')->unique('pet_id')->count(), 'W4');
        });

        $this->assertTrue(WalkSlot::upcoming()->get()->contains(fn (WalkSlot $s) => $s->spotsLeft() === 0));

        $volunteer = User::where('login', 'volunteer')->first();
        $this->assertSame(2, $volunteer->walkBookings()->active()->count());
        $this->assertGreaterThanOrEqual(1, $volunteer->adoptionApplications()->count());
        $this->assertSame(3, $volunteer->favoritePets()->count());
    }
}
