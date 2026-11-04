<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Shelter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_login_page_is_available_for_guests(): void
    {
        $this->get('/login')->assertOk()->assertSee('Логин или телефон');
    }

    public function test_user_logs_in_with_login_case_insensitively(): void
    {
        $user = $this->volunteer(['login' => 'aigerim']);

        $this->post('/login', ['identifier' => '  AIGERIM ', 'password' => 'password'])
            ->assertRedirect(route('cabinet.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function phoneInputs(): array
    {
        return [
            'E.164' => ['+77012345678'],
            'через 8' => ['8 701 234 56 78'],
            'через 7' => ['7 701 234 56 78'],
            'со скобками' => ['+7 (701) 234-56-78'],
        ];
    }

    #[DataProvider('phoneInputs')]
    public function test_user_logs_in_with_phone_in_any_format(string $input): void
    {
        $user = $this->volunteer(['phone' => '+77012345678']);

        $this->post('/login', ['identifier' => $input, 'password' => 'password'])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_shows_generic_error(): void
    {
        $this->volunteer(['login' => 'aigerim']);

        $this->from('/login')->post('/login', ['identifier' => 'aigerim', 'password' => 'wrong-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['identifier' => 'Неверный логин или пароль.']);

        $this->assertGuest();
    }

    public function test_unknown_user_gets_the_same_error(): void
    {
        $this->post('/login', ['identifier' => 'nobody', 'password' => 'password'])
            ->assertSessionHasErrors(['identifier' => 'Неверный логин или пароль.']);
    }

    public function test_blocked_user_cannot_log_in(): void
    {
        User::factory()->blocked()->create(['login' => 'spammer']);

        $this->post('/login', ['identifier' => 'spammer', 'password' => 'password'])
            ->assertSessionHasErrors(['identifier' => __('auth.blocked')]);

        $this->assertGuest();
    }

    public function test_remember_me_sets_recaller_cookie(): void
    {
        $this->volunteer(['login' => 'aigerim']);

        $response = $this->post('/login', ['identifier' => 'aigerim', 'password' => 'password', 'remember' => '1']);

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));
        $this->assertNotNull($cookie);
    }

    public function test_failed_attempts_are_throttled_per_identifier(): void
    {
        $this->volunteer(['login' => 'aigerim']);

        for ($i = 0; $i < LoginRequest::MAX_ATTEMPTS; $i++) {
            $this->post('/login', ['identifier' => 'aigerim', 'password' => 'wrong']);
        }

        // Даже с верным паролем — пока действует блокировка.
        $this->post('/login', ['identifier' => 'aigerim', 'password' => 'password'])
            ->assertSessionHasErrors('identifier');
        $this->assertStringContainsString('Слишком много попыток', session('errors')->first('identifier'));
        $this->assertGuest();
    }

    public function test_login_route_is_rate_limited_by_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['identifier' => "user{$i}", 'password' => 'x']);
        }

        $this->post('/login', ['identifier' => 'another', 'password' => 'x'])->assertTooManyRequests();
    }

    public function test_users_are_redirected_to_their_cabinet_by_role(): void
    {
        $admin = $this->admin();
        $staff = $this->staffOf(Shelter::factory()->create());

        $this->post('/login', ['identifier' => $admin->login, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->post('/logout');

        $this->post('/login', ['identifier' => $staff->login, 'password' => 'password'])
            ->assertRedirect(route('shelter.dashboard'));
    }

    public function test_guest_is_returned_to_intended_page_after_login(): void
    {
        $user = $this->volunteer();

        $this->get('/cabinet/shelter-request')->assertRedirect('/login');

        $this->post('/login', ['identifier' => $user->login, 'password' => 'password'])
            ->assertRedirect('/cabinet/shelter-request');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs($this->volunteer())->get('/login')->assertRedirect(route('cabinet.dashboard'));
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAs($this->volunteer())->post('/logout')->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_logout_requires_post(): void
    {
        $this->actingAs($this->volunteer())->get('/logout')->assertMethodNotAllowed();
    }
}
