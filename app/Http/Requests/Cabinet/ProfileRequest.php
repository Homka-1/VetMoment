<?php

namespace App\Http\Requests\Cabinet;

use App\Rules\KazakhPhone;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Профиль: имя, телефон, почта. Логин не меняется — это идентификатор входа.
 */
class ProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'phone' => Phone::normalize($this->input('phone')) ?? $this->input('phone'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->user()->getKey();

        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', new KazakhPhone, Rule::unique('users', 'phone')->ignore($id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.unique' => 'Этот номер уже занят другим аккаунтом.',
            'email.unique' => 'Эта почта уже занята другим аккаунтом.',
        ];
    }
}
