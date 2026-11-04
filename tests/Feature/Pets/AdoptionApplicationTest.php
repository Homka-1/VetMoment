<?php

namespace Tests\Feature\Pets;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\PetStatus;
use App\Models\AdoptionApplication;
use App\Models\Pet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Заявка «Забрать домой» / «Взять под опеку» (docs/product/user-flows.md, 5).
 */
class AdoptionApplicationTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return [
            'type' => 'adoption',
            'contact_name' => 'Айгерим',
            'contact_phone' => '8 (701) 234-56-78',
            'city' => 'Алматы',
            'living_conditions' => 'Квартира, есть балкон',
            'experience' => 'Была собака 12 лет.',
            'agree' => '1',
            ...$overrides,
        ];
    }

    public function test_guest_is_sent_to_login(): void
    {
        $pet = Pet::factory()->create();

        $this->get('/pets/'.$pet->slug.'/apply')->assertRedirect('/login');
        $this->post('/pets/'.$pet->slug.'/apply', $this->payload())->assertRedirect('/login');
    }

    public function test_form_is_prefilled_from_profile(): void
    {
        $pet = Pet::factory()->create(['guardianship_available' => true]);
        $user = $this->volunteer(['name' => 'Айгерим Сапарова', 'phone' => '+77012345678']);

        $this->actingAs($user)->get('/pets/'.$pet->slug.'/apply?type=guardianship')
            ->assertOk()
            ->assertSee('value="Айгерим Сапарова"', false)
            ->assertSee('+7 701 234 56 78')
            ->assertSee('value="guardianship" checked', false);
    }

    public function test_volunteer_submits_application(): void
    {
        $pet = Pet::factory()->create();
        $user = $this->volunteer();

        $this->actingAs($user)->post('/pets/'.$pet->slug.'/apply', $this->payload())
            ->assertRedirect('/pets/'.$pet->slug)
            ->assertSessionHas('status');

        $application = AdoptionApplication::sole();
        $this->assertSame($user->id, $application->user_id);
        $this->assertSame(ApplicationStatus::New, $application->status);
        $this->assertSame('+77012345678', $application->contact_phone);
        $this->assertSame('Квартира, есть балкон', $application->living_conditions);
    }

    public function test_second_active_application_of_same_type_is_rejected(): void
    {
        $pet = Pet::factory()->create(['guardianship_available' => true]);
        $user = $this->volunteer();
        AdoptionApplication::factory()->for($pet)->for($user)->create();

        $this->actingAs($user)->post('/pets/'.$pet->slug.'/apply', $this->payload())->assertSessionHasErrors('type');
        $this->assertSame(1, AdoptionApplication::count());

        // Другой тип или после отзыва — можно.
        $this->actingAs($user)->post('/pets/'.$pet->slug.'/apply', $this->payload(['type' => 'guardianship', 'living_conditions' => null]))
            ->assertSessionHasNoErrors();
        AdoptionApplication::where('type', ApplicationType::Adoption)->update(['status' => ApplicationStatus::Withdrawn]);
        $this->actingAs($user)->post('/pets/'.$pet->slug.'/apply', $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(3, AdoptionApplication::count());
    }

    public function test_guardianship_only_when_shelter_allows(): void
    {
        $pet = Pet::factory()->create(['guardianship_available' => false]);

        $this->actingAs($this->volunteer())->post('/pets/'.$pet->slug.'/apply', $this->payload(['type' => 'guardianship']))
            ->assertSessionHasErrors('type');
    }

    public function test_validation(): void
    {
        $pet = Pet::factory()->create();

        $this->actingAs($this->volunteer())->post('/pets/'.$pet->slug.'/apply', $this->payload([
            'contact_phone' => '+7 916 123 45 67',
            'living_conditions' => '',
            'agree' => null,
            'experience' => str_repeat('я', 2001),
        ]))->assertSessionHasErrors(['contact_phone', 'living_conditions', 'agree', 'experience']);

        $this->assertSame(0, AdoptionApplication::count());
    }

    public function test_who_cannot_apply(): void
    {
        $pet = Pet::factory()->create();

        $this->actingAs($this->staffOf($pet->shelter))->post('/pets/'.$pet->slug.'/apply', $this->payload())->assertForbidden();
        $this->actingAs($this->admin())->post('/pets/'.$pet->slug.'/apply', $this->payload())->assertForbidden();

        $adopted = Pet::factory()->status(PetStatus::Adopted)->create();
        $this->actingAs($this->volunteer())->get('/pets/'.$adopted->slug.'/apply')->assertForbidden();

        $draft = Pet::factory()->status(PetStatus::Draft)->create();
        $this->actingAs($this->volunteer())->get('/pets/'.$draft->slug.'/apply')->assertNotFound();

        $this->assertSame(0, AdoptionApplication::count());
    }

    public function test_reserved_pet_accepts_application_with_warning(): void
    {
        $pet = Pet::factory()->status(PetStatus::Reserved)->create(['name' => 'Граф']);
        $user = $this->volunteer();

        $this->actingAs($user)->get('/pets/'.$pet->slug.'/apply')->assertOk()->assertSee('Граф уже забронирован');
        $this->actingAs($user)->post('/pets/'.$pet->slug.'/apply', $this->payload())->assertSessionHasNoErrors();
    }

    public function test_user_withdraws_own_application(): void
    {
        $application = AdoptionApplication::factory()->create();

        $this->actingAs($this->volunteer())->post('/applications/'.$application->id.'/withdraw')->assertForbidden();

        $this->actingAs($application->user)->post('/applications/'.$application->id.'/withdraw')->assertSessionHas('status', 'Заявка отозвана');
        $this->assertSame(ApplicationStatus::Withdrawn, $application->fresh()->status);

        // Отозванную — второй раз нельзя.
        $this->actingAs($application->user)->post('/applications/'.$application->id.'/withdraw')->assertForbidden();
    }
}
