<?php

namespace Tests\Feature\Pets;

use App\Enums\ApplicationStatus;
use App\Enums\PetStatus;
use App\Enums\Species;
use App\Models\AdoptionApplication;
use App\Models\Pet;
use App\Models\PetPhoto;
use App\Models\Shelter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Карточка питомца /pets/{slug}: видимость, действия, галерея, похожие.
 */
class PetPageTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_public_pet_page_shows_details_and_actions(): void
    {
        $pet = Pet::factory()->dog()->create([
            'name' => 'Бублик', 'story' => 'Нашли у рынка.', 'guardianship_available' => true, 'good_with_kids' => true,
        ]);
        PetPhoto::factory()->for($pet)->cover()->create();
        PetPhoto::factory()->for($pet)->create(['sort' => 1]);

        $this->get('/pets/'.$pet->slug)
            ->assertOk()
            ->assertSee('Бублик')
            ->assertSee('Нашли у рынка.')
            ->assertSee('Ладит с детьми')
            ->assertSee('Хочу забрать')
            ->assertSee('Взять под опеку')
            ->assertSee('Погулять')
            ->assertSee('Помочь приюту')
            ->assertSee('Бублик — собака, фото 2')
            ->assertSee('og:title', false);
    }

    public function test_unpublished_pets_are_not_found_for_public(): void
    {
        $draft = Pet::factory()->status(PetStatus::Draft)->create();
        $hidden = Pet::factory()->hidden()->create();
        $pending = Pet::factory()->for(Shelter::factory()->pending())->create();

        foreach ([$draft, $hidden, $pending] as $pet) {
            $this->get('/pets/'.$pet->slug)->assertNotFound();
            $this->actingAs($this->volunteer())->get('/pets/'.$pet->slug)->assertNotFound();
            auth()->logout();
        }
    }

    public function test_shelter_staff_and_admin_preview_drafts(): void
    {
        $pet = Pet::factory()->status(PetStatus::Draft)->create();

        $this->actingAs($this->staffOf($pet->shelter))->get('/pets/'.$pet->slug)
            ->assertOk()->assertSee('Анкета не видна на сайте')->assertSee('Редактировать анкету');
        $this->actingAs($this->admin())->get('/pets/'.$pet->slug)->assertOk();
        $this->actingAs($this->staffOf(Shelter::factory()->create()))->get('/pets/'.$pet->slug)->assertNotFound();
    }

    public function test_adopted_pet_has_no_apply_buttons(): void
    {
        $pet = Pet::factory()->status(PetStatus::Adopted)->create(['name' => 'Граф']);

        $this->get('/pets/'.$pet->slug)->assertOk()->assertSee('Граф нашёл дом')->assertDontSee('Хочу забрать');
    }

    public function test_own_shelter_staff_does_not_see_apply_button(): void
    {
        $pet = Pet::factory()->create();

        $this->actingAs($this->staffOf($pet->shelter))->get('/pets/'.$pet->slug)->assertOk()->assertDontSee('Хочу забрать');
    }

    public function test_user_sees_own_application_status_instead_of_button(): void
    {
        $pet = Pet::factory()->create(['guardianship_available' => false]);
        $user = $this->volunteer();
        AdoptionApplication::factory()->for($pet)->for($user)->status(ApplicationStatus::InReview)->create();

        $this->actingAs($user)->get('/pets/'.$pet->slug)
            ->assertSee('Ваша заявка «Забрать домой»')
            ->assertSee('На рассмотрении')
            ->assertSee('Отозвать заявку');
    }

    public function test_similar_pets_are_of_the_same_species_and_public(): void
    {
        $pet = Pet::factory()->dog()->create();
        Pet::factory()->dog()->create(['name' => 'Похожий']);
        Pet::factory()->cat()->create(['name' => 'Котик']);
        Pet::factory()->dog()->status(PetStatus::Draft)->create(['name' => 'Черновой']);

        $similar = $this->get('/pets/'.$pet->slug)->viewData('similar');

        $this->assertSame(['Похожий'], $similar->pluck('name')->all());
        $this->assertTrue($similar->every(fn (Pet $p) => $p->species === Species::Dog));
    }
}
