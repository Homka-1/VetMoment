<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** Общий хеш пароля, чтобы не считать bcrypt для каждой записи. */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'login' => fake()->unique()->userName(),
            // Казахстанский мобильный: +7 7XX XXX XX XX (коды операторов 700–708, 747, 771, 775–778)
            'phone' => '+77'.fake()->randomElement(['00', '01', '02', '05', '07', '08', '47', '71', '75', '76', '77', '78'])
                .fake()->unique()->numerify('#######'),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
}
