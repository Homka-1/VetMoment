<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Вход по логину или телефону + пароль.
 * Неудачные попытки ограничены: MAX_ATTEMPTS в минуту на связку «идентификатор + IP».
 */
class LoginRequest extends FormRequest
{
    public const MAX_ATTEMPTS = 5;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $user = $this->findUser();

        if ($user === null || ! Hash::check($this->string('password')->value(), $user->password)) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages(['identifier' => __('auth.failed')]);
        }

        // Пароль верный, но аккаунт заблокирован — говорим об этом прямо.
        if ($user->is_blocked) {
            throw ValidationException::withMessages(['identifier' => __('auth.blocked')]);
        }

        RateLimiter::clear($this->throttleKey());
        Auth::login($user, $this->boolean('remember'));

        return $user;
    }

    /** Телефон в любом привычном виде → поиск по phone, иначе — по логину (без учёта регистра). */
    private function findUser(): ?User
    {
        $identifier = trim($this->string('identifier')->value());
        $phone = Phone::normalize($identifier);

        return $phone !== null
            ? User::where('phone', $phone)->first()
            : User::where('login', Str::lower($identifier))->first();
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'identifier' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey())]),
        ]);
    }

    private function throttleKey(): string
    {
        $identifier = trim($this->string('identifier')->value());
        $identifier = Phone::normalize($identifier) ?? Str::lower($identifier);

        return 'login:'.Str::transliterate($identifier).'|'.$this->ip();
    }
}
