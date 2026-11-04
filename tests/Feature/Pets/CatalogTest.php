<?php

namespace Tests\Feature\Pets;

use App\Enums\PetSize;
use App\Enums\PetStatus;
use App\Enums\Sex;
use App\Enums\Species;
use App\Models\Pet;
use App\Models\PetPhoto;
use App\Models\Shelter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Публичный каталог /pets: видимость, фильтры, поиск, сортировка, пагинация.
 */
class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private Shelter $shelter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shelter = Shelter::factory()->create(['city' => 'Алматы']);
    }

    private function pet(array $attributes = []): Pet
    {
        return Pet::factory()->for($this->shelter)->create($attributes);
    }

    /** @return list<string> */
    private function names(string $query = ''): array
    {
        $response = $this->get('/pets'.$query)->assertOk();

        return $response->viewData('pets')->pluck('name')->all();
    }

    public function test_catalog_shows_only_pets_looking_for_home(): void
    {
        $this->pet(['name' => 'Видимый']);
        $this->pet(['name' => 'Лечится', 'status' => PetStatus::OnTreatment]);
        $this->pet(['name' => 'Резерв', 'status' => PetStatus::Reserved]);
        $this->pet(['name' => 'Черновик', 'status' => PetStatus::Draft]);
        $this->pet(['name' => 'Архив', 'status' => PetStatus::Archived]);
        $this->pet(['name' => 'Дома', 'status' => PetStatus::Adopted]);
        $this->pet(['name' => 'Скрытый', 'is_hidden' => true]);
        Pet::factory()->for(Shelter::factory()->pending())->create(['name' => 'Непроверенный']);

        $names = $this->names();

        sort($names);
        $this->assertSame(['Видимый', 'Лечится', 'Резерв'], $names);
        $this->assertSame(3, $this->get('/pets')->viewData('total'));
    }

    public function test_filters_by_species_sex_and_size(): void
    {
        $this->pet(['name' => 'Пёс', 'species' => Species::Dog, 'sex' => Sex::Male, 'size' => PetSize::Large]);
        $this->pet(['name' => 'Собачка', 'species' => Species::Dog, 'sex' => Sex::Female, 'size' => PetSize::Small]);
        $this->pet(['name' => 'Кот', 'species' => Species::Cat, 'sex' => Sex::Male, 'size' => PetSize::Small]);

        $this->assertEqualsCanonicalizing(['Пёс', 'Собачка'], $this->names('?species[]=dog'));
        $this->assertEqualsCanonicalizing(['Пёс', 'Кот'], $this->names('?sex[]=male'));
        $this->assertSame(['Собачка'], $this->names('?species[]=dog&size[]=small'));
        $this->assertEqualsCanonicalizing(['Пёс', 'Собачка', 'Кот'], $this->names('?species[]=dog&species[]=cat'));
    }

    public function test_filters_by_age_groups(): void
    {
        $this->pet(['name' => 'Малыш', 'birth_date' => now()->subMonths(5)]);
        $this->pet(['name' => 'Подросток', 'birth_date' => now()->subYears(2)]);
        $this->pet(['name' => 'Взрослый', 'birth_date' => now()->subYears(5)]);
        $this->pet(['name' => 'Старичок', 'birth_date' => now()->subYears(9)]);
        $this->pet(['name' => 'Неизвестно', 'birth_date' => null]);

        $this->assertSame(['Малыш'], $this->names('?age[]=baby'));
        $this->assertSame(['Подросток'], $this->names('?age[]=young'));
        $this->assertSame(['Старичок'], $this->names('?age[]=senior'));
        $this->assertEqualsCanonicalizing(['Малыш', 'Взрослый'], $this->names('?age[]=baby&age[]=adult'));
    }

    public function test_filters_walk_guardianship_city_and_shelter(): void
    {
        $astana = Shelter::factory()->create(['city' => 'Астана']);
        $this->pet(['name' => 'Гуляка', 'can_walk' => true, 'guardianship_available' => false]);
        $this->pet(['name' => 'Домосед', 'can_walk' => false, 'guardianship_available' => true]);
        Pet::factory()->for($astana)->create(['name' => 'Столичный', 'can_walk' => false, 'guardianship_available' => false]);

        $this->assertSame(['Гуляка'], $this->names('?walk=1'));
        $this->assertSame(['Домосед'], $this->names('?guardianship=1'));
        $this->assertSame(['Столичный'], $this->names('?city=Астана'));
        $this->assertSame(['Столичный'], $this->names('?shelter='.$astana->slug));
    }

    public function test_search_by_name_ignores_case_including_cyrillic(): void
    {
        $this->pet(['name' => 'Бублик']);
        $this->pet(['name' => 'Барон']);
        $this->pet(['name' => 'Rex']);

        $this->assertSame(['Бублик'], $this->names('?q='.urlencode('бублик')));
        $this->assertSame(['Бублик'], $this->names('?q='.urlencode('БУБ')));
        $this->assertSame(['Rex'], $this->names('?q=REX'));
        $this->assertSame([], $this->names('?q='.urlencode('%')));
    }

    public function test_sorting_longest_waiting_first_or_newest(): void
    {
        $this->pet(['name' => 'Недавний', 'arrived_at' => now()->subWeek(), 'created_at' => now()->subDays(1)]);
        $this->pet(['name' => 'Старожил', 'arrived_at' => now()->subYear(), 'created_at' => now()->subDays(3)]);
        $this->pet(['name' => 'БезДаты', 'arrived_at' => null, 'created_at' => now()]);

        $this->assertSame(['Старожил', 'Недавний', 'БезДаты'], $this->names());
        $this->assertSame(['БезДаты', 'Недавний', 'Старожил'], $this->names('?sort=new'));
    }

    public function test_unknown_filter_values_are_ignored(): void
    {
        $this->pet(['name' => 'Бублик']);

        $this->assertSame(['Бублик'], $this->names('?species[]=dragon&age[]=old&sort=random&size=huge&q[]=x'));
    }

    public function test_paginates_by_twelve_and_keeps_filters_in_links(): void
    {
        Pet::factory()->count(14)->for($this->shelter)->dog()->create();

        $response = $this->get('/pets?species[]=dog')->assertOk();
        $this->assertCount(12, $response->viewData('pets'));
        $response->assertSee('species%5B0%5D=dog&amp;page=2', false);

        $this->assertCount(2, $this->get('/pets?species[]=dog&page=2')->viewData('pets'));
    }

    public function test_active_filters_are_shown_as_removable_chips(): void
    {
        $this->pet();

        $this->get('/pets?species[]=dog&walk=1')
            ->assertSee('Убрать фильтр «Собаки»', false)
            ->assertSee('Убрать фильтр «Готов к выгулу»', false)
            ->assertSee('href="'.e(url('/pets').'?walk=1').'"', false);
    }

    public function test_empty_result_suggests_resetting_filters(): void
    {
        $this->get('/pets?q=nobody')->assertOk()->assertSee('Никого не нашли')->assertSee('Сбросить фильтры');
    }

    public function test_catalog_has_no_n_plus_one_queries(): void
    {
        foreach (Pet::factory()->count(12)->for($this->shelter)->create() as $pet) {
            PetPhoto::factory()->for($pet)->cover()->create();
        }

        DB::enableQueryLog();
        $this->get('/pets')->assertOk();
        $queries = count(DB::getQueryLog());

        $this->assertLessThan(15, $queries, "Каталог сделал {$queries} запросов");
    }
}
