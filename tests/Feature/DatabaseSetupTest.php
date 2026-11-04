<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_creates_valid_users(): void
    {
        $users = User::factory()->count(5)->create();

        $this->assertCount(5, $users);
        $users->each(fn (User $user) => $this->assertTrue(Phone::isValid($user->phone), $user->phone));
    }

    public function test_seed_if_empty_seeds_only_once(): void
    {
        $this->artisan('app:seed-if-empty')->assertSuccessful();
        $this->assertDatabaseHas('users', ['login' => 'demo']);
        $count = User::count();

        $this->artisan('app:seed-if-empty')
            ->expectsOutputToContain('уже есть данные')
            ->assertSuccessful();
        $this->assertSame($count, User::count());
    }

    public function test_wait_for_db_succeeds_when_database_is_available(): void
    {
        $this->artisan('app:wait-for-db', ['--timeout' => 1])->assertSuccessful();
    }
}
