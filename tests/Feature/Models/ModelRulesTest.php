<?php

namespace Tests\Feature\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PetStatus;
use App\Enums\Role;
use App\Enums\ShelterMemberRole;
use App\Enums\WalkSlotState;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModelRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_slugs_are_latin_and_unique(): void
    {
        $shelter = Shelter::factory()->create(['name' => 'Дом лап', 'city' => 'Алматы']);
        $twin = Shelter::factory()->create(['name' => 'Дом лап', 'city' => 'Алматы']);

        $this->assertSame('dom-lap-almaty', $shelter->slug);
        $this->assertSame('dom-lap-almaty-2', $twin->slug);

        $pets = Pet::factory()->count(2)->for($shelter)->create(['name' => 'Бублик']);

        $pets->each(fn (Pet $pet) => $this->assertMatchesRegularExpression('/^bublik-[a-z0-9]{4}$/', $pet->slug));
        $this->assertNotSame($pets[0]->slug, $pets[1]->slug);
        $this->assertSame('slug', $pets[0]->getRouteKeyName());
    }

    public function test_enum_casts_and_defaults(): void
    {
        $user = User::create([
            'login' => 'new_user', 'name' => 'Новый', 'phone' => '+77011234567',
            'email' => 'new@example.kz', 'password' => 'secret123',
        ]);
        $pet = Pet::factory()->create();

        $this->assertSame(Role::Volunteer, $user->fresh()->role);
        $this->assertFalse($user->fresh()->is_blocked);
        $this->assertSame(PetStatus::LookingForHome, $pet->fresh()->status);
        $this->assertSame(PetStatus::Draft, (new Pet)->status);
    }

    public function test_user_belongs_to_one_shelter_with_member_role(): void
    {
        $owner = User::factory()->shelterStaff()->create();
        $shelter = Shelter::factory()->withOwner($owner)->create();

        $this->assertTrue($owner->shelter()->is($shelter));
        $this->assertSame(ShelterMemberRole::Owner, $owner->shelter()->pivot->role);
        $this->assertTrue($shelter->owners->first()->is($owner));

        // shelter_user.user_id уникален — второй приют не привязать
        $this->expectException(QueryException::class);
        Shelter::factory()->create()->members()->attach($owner, ['role' => ShelterMemberRole::Staff]);
    }

    public function test_favorites_are_unique_per_user_and_pet(): void
    {
        $user = User::factory()->create();
        $pet = Pet::factory()->create();

        $user->favoritePets()->attach($pet);
        $this->assertTrue($pet->favoritedBy->first()->is($user));

        $this->expectException(QueryException::class);
        $user->favoritePets()->attach($pet);
    }

    public function test_visible_and_catalog_scopes(): void
    {
        $approved = Shelter::factory()->create();
        $pending = Shelter::factory()->pending()->create();

        $listed = Pet::factory()->for($approved)->create();
        Pet::factory()->for($approved)->status(PetStatus::OnTreatment)->create();
        $adopted = Pet::factory()->for($approved)->status(PetStatus::Adopted)->create();
        Pet::factory()->for($approved)->status(PetStatus::Draft)->create();
        Pet::factory()->for($approved)->status(PetStatus::Archived)->create();
        Pet::factory()->for($approved)->hidden()->create();
        Pet::factory()->for($pending)->create();

        $this->assertSame(3, Pet::visible()->count());
        $this->assertSame(2, Pet::inCatalog()->count());
        $this->assertTrue(Pet::visible()->get()->contains($adopted));
        $this->assertTrue(Pet::inCatalog()->get()->contains($listed));
    }

    public function test_pet_helpers(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');

        $pet = Pet::factory()->dog()->make([
            'birth_date' => '2024-05-01', 'good_with_kids' => true, 'good_with_cats' => false,
            'good_with_dogs' => false, 'house_trained' => true, 'leash_trained' => false,
        ]);

        $this->assertSame(30, $pet->ageInMonths());
        $this->assertSame(['good_with_kids' => 'Ладит с детьми', 'house_trained' => 'Приучен к дому'], $pet->traitLabels());
        $this->assertTrue($pet->isWalkable());

        $pet->status = PetStatus::Adopted;
        $this->assertFalse($pet->isWalkable());
        $this->assertNull(Pet::factory()->make(['birth_date' => null])->ageInMonths());
    }

    public function test_campaign_totals_count_only_succeeded_donations(): void
    {
        $campaign = DonationCampaign::factory()->create(['goal_amount' => 100_000 * 100]);

        Donation::factory()->forCampaign($campaign)->count(3)->create(['amount' => 20_000 * 100]);
        Donation::factory()->forCampaign($campaign)->status(PaymentStatus::Pending)->create(['provider_payment_id' => null]);
        Donation::factory()->forCampaign($campaign)->status(PaymentStatus::Refunded)->create();
        Donation::factory()->create(['shelter_id' => $campaign->shelter_id]); // общий фонд

        $campaign->recalculateTotals();

        $this->assertSame(60_000 * 100, $campaign->fresh()->raised_amount);
        $this->assertSame(3, $campaign->fresh()->donations_count);
        $this->assertSame(60, $campaign->progressPercent());

        $campaign->raised_amount = 150_000 * 100;
        $this->assertSame(100, $campaign->progressPercent());
    }

    public function test_donation_identifiers_and_public_name(): void
    {
        $donation = Donation::factory()->create(['donor_name' => 'Арман']);

        $this->assertTrue(Str::isUuid($donation->uuid));
        $this->assertTrue(Str::isUuid($donation->idempotency_key));
        $this->assertSame('uuid', $donation->getRouteKeyName());
        $this->assertSame('Арман', $donation->publicDonorName());
        $this->assertSame('Аноним', Donation::factory()->anonymous()->make()->publicDonorName());

        // Идемпотентность webhook: (provider, provider_payment_id) уникальны
        $this->expectException(QueryException::class);
        Donation::factory()->create(['shelter_id' => $donation->shelter_id, 'provider_payment_id' => $donation->provider_payment_id]);
    }

    public function test_walk_slot_state_and_booked_count(): void
    {
        Carbon::setTestNow('2026-11-04 06:00:00');

        $slot = WalkSlot::factory()->startingAt(Carbon::parse('2026-11-05 05:00:00'))->create(['capacity' => 3]);
        $this->assertSame(WalkSlotState::Available, $slot->state());
        $this->assertSame(WalkSlotState::Available, WalkSlot::factory()->make(['capacity' => 2])->state(), 'пустой малый слот — свободен');
        $this->assertSame(60, $slot->durationMinutes());

        WalkBooking::factory()->for($slot, 'slot')->create();
        WalkBooking::factory()->for($slot, 'slot')->status(BookingStatus::Confirmed)->create();
        WalkBooking::factory()->for($slot, 'slot')->status(BookingStatus::Cancelled)->create();
        WalkBooking::factory()->for($slot, 'slot')->status(BookingStatus::Rejected)->create();
        $slot->recalculateBookedCount();

        $this->assertSame(2, $slot->booked_count);
        $this->assertSame(1, $slot->spotsLeft());
        $this->assertSame(WalkSlotState::FewLeft, $slot->state());

        WalkBooking::factory()->for($slot, 'slot')->create();
        $slot->recalculateBookedCount();
        $this->assertSame(WalkSlotState::Full, $slot->state());

        $this->assertSame(WalkSlotState::Past, $slot->state(Carbon::parse('2026-11-05 05:00:00')));
        $this->assertSame(WalkSlotState::Cancelled, WalkSlot::factory()->cancelled()->make()->state());
        $this->assertSame(1, WalkSlot::upcoming()->count());
    }
}
