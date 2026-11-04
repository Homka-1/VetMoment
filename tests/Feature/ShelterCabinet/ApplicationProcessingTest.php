<?php

namespace Tests\Feature\ShelterCabinet;

use App\Enums\ApplicationStatus;
use App\Enums\PetStatus;
use App\Models\AdoptionApplication;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Обработка заявок приютом: new → in_review → approved → completed, отказ с причиной.
 */
class ApplicationProcessingTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    private Pet $pet;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pet = Pet::factory()->create(['name' => 'Бублик']);
        $this->staff = $this->staffOf($this->pet->shelter);
    }

    public function test_list_shows_only_own_applications_by_stage(): void
    {
        AdoptionApplication::factory()->for($this->pet)->create(['contact_name' => 'Новенький']);
        AdoptionApplication::factory()->for($this->pet)->status(ApplicationStatus::InReview)->create(['contact_name' => 'Вработе']);
        AdoptionApplication::factory()->create(['contact_name' => 'Чужой']);

        $this->actingAs($this->staff)->get('/shelter/applications')
            ->assertOk()->assertSee('Новенький')->assertDontSee('Вработе')->assertDontSee('Чужой');
        $this->actingAs($this->staff)->get('/shelter/applications?status=in_review')->assertSee('Вработе')->assertDontSee('Новенький');
        $this->actingAs($this->volunteer())->get('/shelter/applications')->assertForbidden();
    }

    public function test_full_happy_path_makes_pet_adopted(): void
    {
        $application = AdoptionApplication::factory()->for($this->pet)->create();
        $other = AdoptionApplication::factory()->for($this->pet)->create();
        $url = '/shelter/applications/'.$application->id;

        $this->actingAs($this->staff)->get($url)->assertOk()->assertSee('Взять в работу')->assertSee($application->contact_name);

        $this->actingAs($this->staff)->patch($url, ['status' => 'in_review'])->assertSessionHas('status', 'Заявка взята в работу');
        $this->actingAs($this->staff)->patch($url, ['status' => 'approved'])->assertSessionHas('status', 'Заявка одобрена');
        $this->actingAs($this->staff)->patch($url, ['status' => 'completed'])->assertRedirect($url);

        $this->assertSame(ApplicationStatus::Completed, $application->fresh()->status);
        $this->assertSame(PetStatus::Adopted, $this->pet->fresh()->status);
        $this->assertNotNull($this->pet->fresh()->adopted_at);
        $this->assertSame(ApplicationStatus::Rejected, $other->fresh()->status);
        $this->assertSame('Питомец нашёл дом.', $other->fresh()->shelter_comment);
    }

    public function test_reject_requires_reason(): void
    {
        $application = AdoptionApplication::factory()->for($this->pet)->create();
        $url = '/shelter/applications/'.$application->id;

        $this->actingAs($this->staff)->patch($url, ['status' => 'rejected'])->assertSessionHasErrors('shelter_comment');
        $this->actingAs($this->staff)->patch($url, ['status' => 'rejected', 'shelter_comment' => 'Нужен частный дом.'])->assertSessionHasNoErrors();

        $this->assertSame(ApplicationStatus::Rejected, $application->fresh()->status);
        $this->assertSame('Нужен частный дом.', $application->fresh()->shelter_comment);
        $this->assertNotNull($application->fresh()->processed_at);
    }

    public function test_invalid_transitions_are_refused(): void
    {
        $new = AdoptionApplication::factory()->for($this->pet)->create();
        $withdrawn = AdoptionApplication::factory()->for($this->pet)->status(ApplicationStatus::Withdrawn)->create();

        $this->actingAs($this->staff)->patch('/shelter/applications/'.$new->id, ['status' => 'completed'])->assertSessionHasErrors('status');
        $this->actingAs($this->staff)->patch('/shelter/applications/'.$withdrawn->id, ['status' => 'in_review'])->assertSessionHasErrors('status');
        $this->actingAs($this->staff)->patch('/shelter/applications/'.$new->id, ['status' => 'withdrawn'])->assertSessionHasErrors('status');

        $this->assertSame(ApplicationStatus::New, $new->fresh()->status);
        $this->assertSame(PetStatus::LookingForHome, $this->pet->fresh()->status);
    }

    public function test_other_shelter_and_admin_cannot_process(): void
    {
        $application = AdoptionApplication::factory()->for($this->pet)->create();
        $stranger = $this->staffOf(Shelter::factory()->create());

        $this->actingAs($stranger)->get('/shelter/applications/'.$application->id)->assertForbidden();
        $this->actingAs($stranger)->patch('/shelter/applications/'.$application->id, ['status' => 'in_review'])->assertForbidden();
        $this->actingAs($this->admin())->patch('/shelter/applications/'.$application->id, ['status' => 'in_review'])->assertForbidden();
    }
}
