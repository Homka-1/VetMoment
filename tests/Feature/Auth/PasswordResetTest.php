<?php

namespace Tests\Feature\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_forgot_password_page_is_available(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_reset_link_is_sent_by_email(): void
    {
        Notification::fake();
        $user = $this->volunteer(['email' => 'dana@example.kz']);

        $this->post('/forgot-password', ['email' => 'DANA@example.kz '])
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_email_is_in_russian(): void
    {
        Notification::fake();
        $user = $this->volunteer();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Смена пароля на VetMoment'
                && $mail->actionText === 'Сменить пароль'
                && str_contains($mail->actionUrl, '/reset-password/');
        });
    }

    public function test_unknown_email_gets_the_same_answer(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.kz'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertNothingSent();
    }

    public function test_reset_page_opens_with_token(): void
    {
        $this->get('/reset-password/some-token?email=dana@example.kz')
            ->assertOk()
            ->assertSee('dana@example.kz');
    }

    public function test_password_is_reset_with_valid_token(): void
    {
        $user = $this->volunteer();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secret-pass',
            'password_confirmation' => 'new-secret-pass',
        ])->assertRedirect(route('login'))->assertSessionHas('status', __('passwords.reset'));

        $this->assertTrue(Hash::check('new-secret-pass', $user->fresh()->password));
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = $this->volunteer();

        $this->post('/reset-password', [
            'token' => 'invalid',
            'email' => $user->email,
            'password' => 'new-secret-pass',
            'password_confirmation' => 'new-secret-pass',
        ])->assertSessionHasErrors(['email' => __('passwords.token')]);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_link_requests_are_rate_limited(): void
    {
        Notification::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['email' => 'dana@example.kz']);
        }

        $this->post('/forgot-password', ['email' => 'dana@example.kz'])->assertTooManyRequests();
    }
}
