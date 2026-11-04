<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Демо-данные. Полный набор (приюты, питомцы, сборы, слоты) — на этапе 5.
     */
    public function run(): void
    {
        User::factory()->create([
            'login' => 'demo',
            'phone' => '+77010000001',
        ]);
    }
}
