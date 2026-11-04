<?php

namespace Database\Factories;

use App\Enums\PetSize;
use App\Enums\PetStatus;
use App\Enums\Sex;
use App\Enums\Species;
use App\Models\Pet;
use App\Models\Shelter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pet>
 */
class PetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $species = fake()->randomElement([Species::Dog, Species::Dog, Species::Cat]);
        $isDog = $species === Species::Dog;

        return [
            'shelter_id' => Shelter::factory(),
            'name' => fake()->randomElement($isDog
                ? ['Бублик', 'Граф', 'Барон', 'Лорд', 'Рыжик', 'Тайга', 'Бим', 'Джесси', 'Алтай', 'Найда']
                : ['Мята', 'Мурка', 'Пушок', 'Ириска', 'Сёма', 'Луна', 'Барсик', 'Тучка']),
            'species' => $species,
            'sex' => fake()->randomElement([Sex::Male, Sex::Female]),
            'birth_date' => fake()->dateTimeBetween('-10 years', '-3 months'),
            'birth_date_is_approx' => fake()->boolean(70),
            'size' => $isDog ? fake()->randomElement(PetSize::cases()) : PetSize::Small,
            'breed' => 'метис',
            'color' => fake()->randomElement(['рыжий', 'чёрный', 'серый', 'белый', 'трёхцветный', 'палевый']),
            'character' => 'Ласковый и спокойный, быстро привыкает к людям и любит, когда с ним разговаривают.',
            'good_with_kids' => fake()->boolean(),
            'good_with_cats' => fake()->boolean(),
            'good_with_dogs' => fake()->boolean(),
            'house_trained' => fake()->boolean(),
            'leash_trained' => $isDog && fake()->boolean(70),
            'vaccinated' => fake()->boolean(85),
            'sterilized' => fake()->boolean(75),
            'chipped' => fake()->boolean(60),
            'status' => PetStatus::LookingForHome,
            'can_walk' => $isDog,
            'guardianship_available' => fake()->boolean(40),
            'arrived_at' => fake()->dateTimeBetween('-2 years', '-1 week'),
            'is_hidden' => false,
        ];
    }

    public function dog(): static
    {
        return $this->state(['species' => Species::Dog, 'can_walk' => true]);
    }

    public function cat(): static
    {
        return $this->state(['species' => Species::Cat, 'can_walk' => false, 'size' => PetSize::Small]);
    }

    public function status(PetStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'adopted_at' => $status === PetStatus::Adopted ? now()->subDays(fake()->numberBetween(1, 90)) : null,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(['is_hidden' => true]);
    }
}
