<?php

namespace Tests\Feature\Policies;

use App\Enums\ShelterMemberRole;
use App\Models\Shelter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Права на приюты — docs/product/roles.md, 2.1 и 2.5.
 */
class ShelterPolicyTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_only_approved_shelters_are_public(): void
    {
        $this->assertTrue(Gate::forUser(null)->allows('view', Shelter::factory()->create()));
        $this->assertTrue(Gate::forUser(null)->allows('donate', Shelter::factory()->create()));

        foreach (['pending', 'rejected', 'blocked'] as $state) {
            $shelter = Shelter::factory()->{$state}()->create();
            $this->assertFalse(Gate::forUser(null)->allows('view', $shelter), $state);
            $this->assertFalse(Gate::forUser(null)->allows('donate', $shelter), $state);
            $this->assertTrue(Gate::forUser($this->staffOf($shelter))->allows('view', $shelter), $state);
            $this->assertTrue(Gate::forUser($this->admin())->allows('view', $shelter), $state);
        }
    }

    public function test_only_volunteer_without_shelter_can_register_one(): void
    {
        $this->assertTrue(Gate::forUser($this->volunteer())->allows('register', Shelter::class));
        $this->assertFalse(Gate::forUser($this->staffOf(Shelter::factory()->create()))->allows('register', Shelter::class));
        $this->assertFalse(Gate::forUser($this->admin())->allows('register', Shelter::class));
    }

    public function test_owner_manages_profile_and_staff_regular_staff_does_not(): void
    {
        $shelter = Shelter::factory()->create();
        $owner = $this->staffOf($shelter);
        $staff = $this->staffOf($shelter, ShelterMemberRole::Staff);
        $other = $this->staffOf(Shelter::factory()->create());

        foreach (['update', 'manageStaff'] as $ability) {
            $this->assertTrue(Gate::forUser($owner)->allows($ability, $shelter), $ability);
            $this->assertTrue(Gate::forUser($this->admin())->allows($ability, $shelter), $ability);
            $this->assertFalse(Gate::forUser($staff)->allows($ability, $shelter), $ability);
            $this->assertFalse(Gate::forUser($other)->allows($ability, $shelter), $ability);
        }

        $this->assertTrue(Gate::forUser($staff)->allows('manage', $shelter), 'сотрудник работает в кабинете');
        $this->assertFalse(Gate::forUser($other)->allows('manage', $shelter));
        $this->assertFalse(Gate::forUser($this->volunteer())->allows('manage', $shelter));
    }

    public function test_resubmit_only_rejected_by_owner(): void
    {
        $rejected = Shelter::factory()->rejected()->create();
        $pending = Shelter::factory()->pending()->create();

        $this->assertTrue(Gate::forUser($this->staffOf($rejected))->allows('resubmit', $rejected));
        $this->assertFalse(Gate::forUser($this->staffOf($rejected, ShelterMemberRole::Staff))->allows('resubmit', $rejected));
        $this->assertFalse(Gate::forUser($this->staffOf($pending))->allows('resubmit', $pending));
        $this->assertFalse(Gate::forUser($this->admin())->allows('resubmit', $rejected));
    }

    public function test_moderation_is_admin_only(): void
    {
        $pending = Shelter::factory()->pending()->create();

        foreach (['approve', 'reject'] as $ability) {
            $this->assertTrue(Gate::forUser($this->admin())->allows($ability, $pending), $ability);
            $this->assertFalse(Gate::forUser($this->staffOf($pending))->allows($ability, $pending), $ability);
            $this->assertFalse(Gate::forUser($this->volunteer())->allows($ability, $pending), $ability);
        }
    }
}
