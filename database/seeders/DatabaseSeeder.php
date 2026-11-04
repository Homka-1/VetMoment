<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Без WithoutModelEvents: модели генерируют slug и uuid в событии creating.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
