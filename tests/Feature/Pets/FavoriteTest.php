<?php

namespace Tests\Feature\Pets;

use App\Enums\PetStatus;
use App\Models\Pet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Избранное: сердечко на карточке и страница /cabinet/favorites.
 */
class FavoriteTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_guest_is_sent_to_login_and_back(): void
    {
        $pet = Pet::factory()->create();

        $this->from('/pets')->post('/pets/'.$pet->slug.'/favorite')->assertRedirect('/login');
        $this->assertSame(url('/pets'), session('url.intended'));
    }

    public function test_toggle_adds_and_removes(): void
    {
        $pet = Pet::factory()->create(['name' => 'Мята']);
        $user = $this->volunteer();

        $this->actingAs($user)->from('/pets')->post('/pets/'.$pet->slug.'/favorite')
            ->assertRedirect('/pets')->assertSessionHas('status', 'Мята — в избранном');
        $this->assertTrue($user->favoritePets()->whereKey($pet->id)->exists());

        $this->actingAs($user)->post('/pets/'.$pet->slug.'/favorite')->assertSessionHas('status', 'Мята — убрали из избранного');
        $this->assertFalse($user->favoritePets()->whereKey($pet->id)->exists());
    }

    public function test_cannot_favorite_unpublished_pet(): void
    {
        $pet = Pet::factory()->status(PetStatus::Draft)->create();

        $this->actingAs($this->volunteer())->post('/pets/'.$pet->slug.'/favorite')->assertForbidden();
    }

    public function test_catalog_marks_favorites(): void
    {
        $pet = Pet::factory()->create(['name' => 'Мята']);
        $user = $this->volunteer();
        $user->favoritePets()->attach($pet);

        $this->actingAs($user)->get('/pets')->assertSee('Убрать Мята из избранного');
    }

    public function test_favorites_page_lists_only_visible_pets(): void
    {
        $user = $this->volunteer();
        $user->favoritePets()->attach(Pet::factory()->create(['name' => 'Видимый']));
        $user->favoritePets()->attach(Pet::factory()->status(PetStatus::Archived)->create(['name' => 'Архивный']));

        $this->actingAs($user)->get('/cabinet/favorites')->assertOk()->assertSee('Видимый')->assertDontSee('Архивный');
        $this->actingAs($this->volunteer())->get('/cabinet/favorites')->assertOk()->assertSee('Здесь будут питомцы');
        auth()->logout();
        $this->get('/cabinet/favorites')->assertRedirect('/login');
    }
}
