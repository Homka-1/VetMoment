<?php

namespace Tests\Feature\ShelterCabinet;

use App\Enums\ApplicationStatus;
use App\Enums\PetStatus;
use App\Models\AdoptionApplication;
use App\Models\Pet;
use App\Models\PetPhoto;
use App\Models\Shelter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesActors;
use Tests\Concerns\MakesImages;
use Tests\TestCase;

/**
 * Анкеты в кабинете приюта: права, создание, фото, публикация, архив (docs/product/user-flows.md, 3).
 */
class PetManagementTest extends TestCase
{
    use CreatesActors, MakesImages, RefreshDatabase;

    private Shelter $shelter;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->shelter = Shelter::factory()->create();
        $this->staff = $this->staffOf($this->shelter);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Кнопка',
            'species' => 'cat',
            'sex' => 'female',
            'size' => 'small',
            'birth_date' => now()->subMonths(2)->toDateString(),
            'birth_date_is_approx' => '1',
            'character' => 'Любопытная и ласковая, мурчит на руках.',
            'good_with_kids' => '1',
            'vaccinated' => '1',
            'arrived_at' => now()->subWeek()->toDateString(),
            ...$overrides,
        ];
    }

    public function test_access_to_the_section(): void
    {
        $this->get('/shelter/pets')->assertRedirect('/login');
        $this->actingAs($this->volunteer())->get('/shelter/pets')->assertForbidden();
        $this->actingAs($this->admin())->get('/shelter/pets')->assertForbidden();
        $this->actingAs($this->staff)->get('/shelter/pets')->assertOk();
    }

    public function test_index_lists_only_own_pets_with_status_filter(): void
    {
        Pet::factory()->for($this->shelter)->create(['name' => 'Свой']);
        Pet::factory()->for($this->shelter)->status(PetStatus::Draft)->create(['name' => 'Набросок']);
        Pet::factory()->for($this->shelter)->status(PetStatus::Archived)->create(['name' => 'Архивный']);
        Pet::factory()->create(['name' => 'Чужой']);

        $this->actingAs($this->staff)->get('/shelter/pets')
            ->assertSee('Свой')->assertSee('Набросок')->assertDontSee('Архивный')->assertDontSee('Чужой');
        $this->actingAs($this->staff)->get('/shelter/pets?status=draft')
            ->assertSee('Набросок')->assertDontSee('>Свой<', false);
        $this->actingAs($this->staff)->get('/shelter/pets?status=archived')->assertSee('Архивный');
        $this->actingAs($this->staff)->get('/shelter/pets?q='.urlencode('свой'))->assertSee('Свой')->assertDontSee('Набросок');
    }

    public function test_publish_new_pet_with_photos(): void
    {
        $this->actingAs($this->staff)->post('/shelter/pets', $this->payload([
            'action' => 'publish',
            'photos_new' => [$this->png('a.png'), $this->png('b.png')],
            'cover' => 'new:1',
        ]))->assertRedirect('/shelter/pets')->assertSessionHas('status', 'Анкета «Кнопка» опубликована');

        $pet = Pet::sole();
        $this->assertSame($this->shelter->id, $pet->shelter_id);
        $this->assertSame(PetStatus::LookingForHome, $pet->status);
        $this->assertTrue($pet->birth_date_is_approx);
        $this->assertTrue($pet->good_with_kids);
        $this->assertFalse($pet->good_with_cats);
        $this->assertStringStartsWith('knopka-', $pet->slug);

        $photos = $pet->photos;
        $this->assertCount(2, $photos);
        $this->assertSame([0, 1], $photos->pluck('sort')->all());
        $this->assertSame([false, true], $photos->pluck('is_cover')->all());
        $photos->each(fn (PetPhoto $p) => Storage::disk('public')->assertExists($p->path));

        $this->get('/pets/'.$pet->slug)->assertOk();
    }

    public function test_draft_can_be_saved_without_photos(): void
    {
        $this->actingAs($this->staff)->post('/shelter/pets', $this->payload(['action' => 'draft']))
            ->assertSessionHas('status', 'Черновик «Кнопка» сохранён');

        $this->assertSame(PetStatus::Draft, Pet::sole()->status);
    }

    public function test_publication_requires_a_photo(): void
    {
        $this->actingAs($this->staff)->post('/shelter/pets', $this->payload(['action' => 'publish']))
            ->assertSessionHasErrors('photos_new');

        $this->assertSame(0, Pet::count());
    }

    public function test_shelter_on_moderation_saves_only_drafts(): void
    {
        $pending = Shelter::factory()->pending()->create();
        $staff = $this->staffOf($pending);

        $this->actingAs($staff)->get('/shelter/pets')->assertSee('Приют на проверке');
        $this->actingAs($staff)->post('/shelter/pets', $this->payload(['action' => 'publish', 'photos_new' => [$this->png()]]))
            ->assertSessionHasErrors('status');
        $this->actingAs($staff)->post('/shelter/pets', $this->payload(['action' => 'draft', 'photos_new' => [$this->png()]]))
            ->assertSessionHasNoErrors();

        $pet = Pet::sole();
        $this->assertSame(PetStatus::Draft, $pet->status);
        $this->actingAs($staff)->put('/shelter/pets/'.$pet->slug, $this->payload(['status' => 'looking_for_home']))
            ->assertSessionHasErrors('status');
    }

    public function test_validation_of_fields_and_files(): void
    {
        $this->actingAs($this->staff)->post('/shelter/pets', $this->payload([
            'action' => 'draft',
            'name' => '',
            'species' => 'dragon',
            'character' => 'коротко',
            'birth_date' => now()->addDay()->toDateString(),
            'photos_new' => [UploadedFile::fake()->createWithContent('doc.pdf', '%PDF-1.4')],
        ]))->assertSessionHasErrors(['name', 'species', 'character', 'birth_date', 'photos_new.0']);

        $this->actingAs($this->staff)->post('/shelter/pets', $this->payload([
            'action' => 'draft',
            'photos_new' => [UploadedFile::fake()->create('big.jpg', 9000, 'image/jpeg')],
        ]))->assertSessionHasErrors('photos_new.0');

        $this->assertSame(0, Pet::count());
    }

    public function test_no_more_than_ten_photos(): void
    {
        $pet = Pet::factory()->for($this->shelter)->create();
        PetPhoto::factory()->count(9)->for($pet)->create();

        $this->actingAs($this->staff)->put('/shelter/pets/'.$pet->slug, $this->payload([
            'status' => 'looking_for_home',
            'photos_new' => [$this->png('a.png'), $this->png('b.png')],
        ]))->assertSessionHasErrors('photos_new');

        // Если одно удалить — два новых помещаются.
        $first = $pet->photos()->first();
        $this->actingAs($this->staff)->put('/shelter/pets/'.$pet->slug, $this->payload([
            'status' => 'looking_for_home',
            'photos' => [$first->id => ['delete' => '1']],
            'photos_new' => [$this->png('a.png'), $this->png('b.png')],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(10, $pet->photos()->count());
    }

    public function test_photos_reorder_cover_and_delete(): void
    {
        $pet = Pet::factory()->for($this->shelter)->create();
        Storage::disk('public')->put('pets/one.jpg', 'x');
        $one = PetPhoto::factory()->for($pet)->cover()->create(['path' => 'pets/one.jpg', 'sort' => 0]);
        $two = PetPhoto::factory()->for($pet)->create(['sort' => 1]);
        $three = PetPhoto::factory()->for($pet)->create(['sort' => 2]);

        $this->actingAs($this->staff)->put('/shelter/pets/'.$pet->slug, $this->payload([
            'status' => 'looking_for_home',
            'photos' => [
                $one->id => ['sort' => '2', 'delete' => '1'],
                $two->id => ['sort' => '1'],
                $three->id => ['sort' => '0'],
            ],
            'cover' => 'existing:'.$two->id,
        ]))->assertRedirect('/shelter/pets/'.$pet->slug.'/edit')->assertSessionHasNoErrors();

        $this->assertModelMissing($one);
        Storage::disk('public')->assertMissing('pets/one.jpg');
        $this->assertSame([$three->id, $two->id], $pet->photos()->pluck('id')->all());
        $this->assertSame($two->id, $pet->cover()->value('id'));
    }

    public function test_deleting_cover_promotes_first_photo(): void
    {
        $pet = Pet::factory()->for($this->shelter)->create();
        $cover = PetPhoto::factory()->for($pet)->cover()->create(['sort' => 0]);
        $next = PetPhoto::factory()->for($pet)->create(['sort' => 1]);

        $this->actingAs($this->staff)->put('/shelter/pets/'.$pet->slug, $this->payload([
            'status' => 'looking_for_home',
            'photos' => [$cover->id => ['delete' => '1']],
        ]))->assertSessionHasNoErrors();

        $this->assertTrue($next->fresh()->is_cover);
    }

    public function test_other_shelter_cannot_edit(): void
    {
        $pet = Pet::factory()->create();

        $this->actingAs($this->staff)->get('/shelter/pets/'.$pet->slug.'/edit')->assertForbidden();
        $this->actingAs($this->staff)->put('/shelter/pets/'.$pet->slug, $this->payload(['status' => 'draft']))->assertForbidden();
        $this->actingAs($this->staff)->delete('/shelter/pets/'.$pet->slug)->assertForbidden();
    }

    public function test_archive_instead_of_delete(): void
    {
        $pet = Pet::factory()->for($this->shelter)->create(['name' => 'Бим']);

        $this->actingAs($this->staff)->delete('/shelter/pets/'.$pet->slug)
            ->assertRedirect('/shelter/pets')->assertSessionHas('status', 'Анкета «Бим» перенесена в архив');

        $this->assertSame(PetStatus::Archived, $pet->fresh()->status);
        auth()->logout();
        $this->get('/pets/'.$pet->slug)->assertNotFound();
    }

    public function test_marking_adopted_closes_open_applications(): void
    {
        $pet = Pet::factory()->for($this->shelter)->create();
        PetPhoto::factory()->for($pet)->cover()->create();
        $approved = AdoptionApplication::factory()->for($pet)->status(ApplicationStatus::Approved)->create();
        $waiting = AdoptionApplication::factory()->for($pet)->create();
        $withdrawn = AdoptionApplication::factory()->for($pet)->status(ApplicationStatus::Withdrawn)->create();

        $this->actingAs($this->staff)->put('/shelter/pets/'.$pet->slug, $this->payload(['status' => 'adopted']))
            ->assertSessionHasNoErrors();

        $this->assertSame(PetStatus::Adopted, $pet->fresh()->status);
        $this->assertNotNull($pet->fresh()->adopted_at);
        $this->assertSame(ApplicationStatus::Completed, $approved->fresh()->status);
        $this->assertSame(ApplicationStatus::Rejected, $waiting->fresh()->status);
        $this->assertSame(ApplicationStatus::Withdrawn, $withdrawn->fresh()->status);
    }

    public function test_edit_form_shows_existing_photos(): void
    {
        $pet = Pet::factory()->for($this->shelter)->create(['name' => 'Бим']);
        $photo = PetPhoto::factory()->for($pet)->cover()->create();

        $this->actingAs($this->staff)->get('/shelter/pets/'.$pet->slug.'/edit')
            ->assertOk()
            ->assertSee('Анкета: Бим')
            ->assertSee('name="photos['.$photo->id.'][sort]"', false)
            ->assertSee('value="existing:'.$photo->id.'"', false);
        $this->actingAs($this->staff)->get('/shelter/pets/create')->assertOk()->assertSee('Сохранить черновик');
    }
}
