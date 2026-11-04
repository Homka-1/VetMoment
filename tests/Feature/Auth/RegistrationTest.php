<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'login' => 'Dana.K',
            'name' => 'Дана Кайратовна',
            'phone' => '8 (707) 123-45-67',
            'email' => 'Dana@Example.kz',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'agree' => '1',
            ...$overrides,
        ];
    }

    public function test_registration_page_is_available(): void
    {
        $this->get('/register')->assertOk()->assertSee('Регистрация');
    }

    public function test_user_registers_as_volunteer_with_normalized_contacts(): void
    {
        $this->post('/register', $this->payload())->assertRedirect(route('cabinet.dashboard'));

        $user = User::firstWhere('login', 'dana.k');
        $this->assertNotNull($user);
        $this->assertSame('+77071234567', $user->phone);
        $this->assertSame('dana@example.kz', $user->email);
        $this->assertSame(Role::Volunteer, $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_role_cannot_be_chosen_on_registration(): void
    {
        $this->post('/register', $this->payload(['role' => 'admin']));

        $this->assertSame(Role::Volunteer, User::firstWhere('login', 'dana.k')->role);
    }

    public function test_duplicates_are_rejected_after_normalization(): void
    {
        $this->volunteer(['login' => 'dana.k', 'phone' => '+77071234567', 'email' => 'dana@example.kz']);

        $this->post('/register', $this->payload())
            ->assertSessionHasErrors(['login', 'phone', 'email']);
    }

    public function test_russian_phone_is_rejected(): void
    {
        $this->post('/register', $this->payload(['phone' => '+7 916 123 45 67']))
            ->assertSessionHasErrors(['phone' => __('validation.kz_phone')]);
    }

    public function test_login_format_is_validated(): void
    {
        $this->post('/register', $this->payload(['login' => 'Дана']))
            ->assertSessionHasErrors(['login' => __('validation.login_format')]);

        $this->post('/register', $this->payload(['login' => 'ab']))->assertSessionHasErrors('login');
    }

    public function test_password_rules(): void
    {
        $this->post('/register', $this->payload(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors('password');

        $this->post('/register', $this->payload(['password_confirmation' => 'other-pass']))
            ->assertSessionHasErrors('password');
    }

    public function test_personal_data_consent_is_required(): void
    {
        $this->post('/register', $this->payload(['agree' => null]))->assertSessionHasErrors('agree');

        $this->assertGuest();
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/register', $this->payload(['login' => "u{$i}", 'agree' => null]));
        }

        $this->post('/register', $this->payload())->assertTooManyRequests();
    }
}
