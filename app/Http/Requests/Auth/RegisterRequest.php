<?php

namespace App\Http\Requests\Auth;

use App\Rules\KazakhPhone;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Регистрация волонтёра. Телефон нормализуется до проверки уникальности.
 */
class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'login' => Str::lower(trim((string) $this->input('login'))),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'name' => trim((string) $this->input('name')),
            'phone' => Phone::normalize($this->input('phone')) ?? $this->input('phone'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-z0-9_.\-]+$/', Rule::unique('users', 'login')],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', new KazakhPhone, Rule::unique('users', 'phone')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'agree' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.regex' => __('validation.login_format'),
            'login.unique' => 'Этот логин уже занят.',
            'phone.unique' => 'Этот номер уже зарегистрирован. Войдите или восстановите пароль.',
            'email.unique' => 'Эта почта уже зарегистрирована. Войдите или восстановите пароль.',
            'agree.accepted' => 'Нужно согласие на обработку персональных данных.',
        ];
    }
}
