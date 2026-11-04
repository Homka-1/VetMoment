<?php

namespace Database\Factories;

use App\Enums\ShelterMemberRole;
use App\Enums\ShelterStatus;
use App\Models\Shelter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shelter>
 */
class ShelterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = fake()->randomElement(['Алматы', 'Астана', 'Шымкент', 'Караганда', 'Актобе', 'Павлодар']);

        return [
            'name' => 'Приют «'.fake()->randomElement([
                'Дом лап', 'Мурлыка', 'Верный друг', 'Хвостики', 'Котопёс', 'Тёплый нос', 'Лапа помощи',
                'Добрые руки', 'Найдёныш', 'Счастливый хвост', 'Друг рядом', 'Пушистый остров',
            ]).'»',
            'description' => fake()->realText(300),
            'city' => $city,
            'address' => $city.', ул. '.fake()->streetName().', '.fake()->buildingNumber(),
            'phone' => '+7701'.fake()->numerify('#######'),
            'email' => fake()->unique()->safeEmail(),
            'website' => null,
            'bin_iin' => null,
            'walk_rules' => 'Возьмите удостоверение личности. Не отпускайте собаку с поводка.',
            'status' => ShelterStatus::Approved,
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => ShelterStatus::Pending, 'approved_at' => null]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => ShelterStatus::Rejected,
            'approved_at' => null,
            'moderation_comment' => 'Не удалось подтвердить контакты приюта.',
        ]);
    }

    public function blocked(): static
    {
        return $this->state(['status' => ShelterStatus::Blocked, 'moderation_comment' => 'Жалобы волонтёров.']);
    }

    /** Приют с владельцем-сотрудником (роль shelter_staff). */
    public function withOwner(?User $owner = null): static
    {
        return $this->afterCreating(function (Shelter $shelter) use ($owner): void {
            $owner ??= User::factory()->shelterStaff()->create();
            $shelter->members()->attach($owner, ['role' => ShelterMemberRole::Owner]);
        });
    }
}
