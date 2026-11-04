<?php

namespace Tests\Feature\Policies;

use App\Enums\PetStatus;
use App\Models\Pet;
use App\Models\Shelter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Матрица прав на анкеты питомцев — docs/product/roles.md, 2.2.
 */
class PetPolicyTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_published_pet_is_visible_to_everyone_including_guests(): void
    {
        $pet = Pet::factory()->create();

        $this->assertTrue(Gate::forUser(null)->allows('view', $pet));
        $this->assertTrue(Gate::forUser($this->volunteer())->allows('view', $pet));
    }

    public function test_unpublished_pet_is_visible_only_to_own_shelter_and_admin(): void
    {
        $shelter = Shelter::factory()->create();
        $draft = Pet::factory()->for($shelter)->status(PetStatus::Draft)->create();
        $hidden = Pet::factory()->for($shelter)->hidden()->create();
        $ofPendingShelter = Pet::factory()->for(Shelter::factory()->pending())->create();

        $own = $this->staffOf($shelter);
        $other = $this->staffOf(Shelter::factory()->create());

        foreach ([$draft, $hidden] as $pet) {
            $this->assertFalse(Gate::forUser(null)->allows('view', $pet));
            $this->assertFalse(Gate::forUser($this->volunteer())->allows('view', $pet));
            $this->assertFalse(Gate::forUser($other)->allows('view', $pet));
            $this->assertTrue(Gate::forUser($own)->allows('view', $pet));
            $this->assertTrue(Gate::forUser($this->admin())->allows('view', $pet));
        }

        $this->assertFalse(Gate::forUser(null)->allows('view', $ofPendingShelter));
    }

    public function test_only_shelter_staff_and_admin_create_pets(): void
    {
        $this->assertFalse(Gate::forUser($this->volunteer())->allows('create', Pet::class));
        $this->assertTrue(Gate::forUser($this->staffOf(Shelter::factory()->pending()->create()))->allows('create', Pet::class));
        $this->assertTrue(Gate::forUser($this->admin())->allows('create', Pet::class));
    }

    public function test_management_is_limited_to_own_shelter(): void
    {
        $shelter = Shelter::factory()->create();
        $pet = Pet::factory()->for($shelter)->create();
        $own = $this->staffOf($shelter);
        $other = $this->staffOf(Shelter::factory()->create());

        foreach (['update', 'delete', 'viewApplications', 'publish'] as $ability) {
            $this->assertTrue(Gate::forUser($own)->allows($ability, $pet), $ability);
            $this->assertTrue(Gate::forUser($this->admin())->allows($ability, $pet), $ability);
            $this->assertFalse(Gate::forUser($other)->allows($ability, $pet), $ability);
            $this->assertFalse(Gate::forUser($this->volunteer())->allows($ability, $pet), $ability);
        }
    }

    public function test_pending_shelter_keeps_drafts_but_cannot_publish(): void
    {
        $shelter = Shelter::factory()->pending()->create();
        $pet = Pet::factory()->for($shelter)->status(PetStatus::Draft)->create();
        $own = $this->staffOf($shelter);

        $this->assertTrue(Gate::forUser($own)->allows('update', $pet));
        $this->assertFalse(Gate::forUser($own)->allows('publish', $pet));
    }

    public function test_only_admin_hides_pets(): void
    {
        $shelter = Shelter::factory()->create();
        $pet = Pet::factory()->for($shelter)->create();

        $this->assertTrue(Gate::forUser($this->admin())->allows('hide', $pet));
        $this->assertFalse(Gate::forUser($this->staffOf($shelter))->allows('hide', $pet));
    }

    public function test_application_rules(): void
    {
        $shelter = Shelter::factory()->create();
        $pet = Pet::factory()->for($shelter)->create();

        $this->assertTrue(Gate::forUser($this->volunteer())->allows('apply', $pet));
        $this->assertTrue(Gate::forUser($this->staffOf(Shelter::factory()->create()))->allows('apply', $pet));
        $this->assertFalse(Gate::forUser($this->staffOf($shelter))->allows('apply', $pet), 'не в свой приют');
        $this->assertFalse(Gate::forUser($this->admin())->allows('apply', $pet), 'админ не подаёт заявки');

        $reserved = Pet::factory()->for($shelter)->status(PetStatus::Reserved)->create();
        $this->assertTrue(Gate::forUser($this->volunteer())->allows('apply', $reserved), 'в резерв');

        foreach ([PetStatus::Adopted, PetStatus::Archived, PetStatus::Draft] as $status) {
            $closed = Pet::factory()->for($shelter)->status($status)->create();
            $this->assertFalse(Gate::forUser($this->volunteer())->allows('apply', $closed), $status->value);
        }
    }

    public function test_favorites_require_public_pet(): void
    {
        $user = $this->volunteer();

        $this->assertTrue(Gate::forUser($user)->allows('favorite', Pet::factory()->create()));
        $this->assertFalse(Gate::forUser($user)->allows('favorite', Pet::factory()->hidden()->create()));
        $this->assertFalse(Gate::forUser(null)->allows('favorite', Pet::factory()->create()), 'гость → вход');
    }
}
