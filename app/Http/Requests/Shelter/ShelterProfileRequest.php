<?php

namespace App\Http\Requests\Shelter;

use App\Rules\KazakhPhone;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Профиль приюта: описание, контакты, правила выгула, логотип. БИН/ИИН и статус модерации не меняются.
 * Кто может — ShelterPolicy::update (владелец приюта).
 */
class ShelterProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shelter = $this->user()->shelter();

        return $shelter !== null && $this->user()->can('update', $shelter);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'city' => trim((string) $this->input('city')),
            'address' => trim((string) $this->input('address')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'website' => trim((string) $this->input('website')) ?: null,
            'phone' => Phone::normalize($this->input('phone')) ?? $this->input('phone'),
            'walk_rules' => trim((string) $this->input('walk_rules')) ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new KazakhPhone],
            'email' => ['required', 'string', 'email', 'max:255'],
            'description' => ['required', 'string', 'min:50', 'max:5000'],
            'website' => ['nullable', 'string', 'url:http,https', 'max:255'],
            'walk_rules' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone' => 'Укажите казахстанский номер, например +7 727 123 45 67 или +7 701 234 56 78.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Название приюта',
            'email' => 'Эл. почта приюта',
            'phone' => 'Телефон приюта',
            'walk_rules' => 'Правила выгула',
        ];
    }
}
