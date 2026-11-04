<?php

namespace App\Http\Requests\Cabinet;

use App\Models\Shelter;
use App\Rules\BinIin;
use App\Rules\KazakhPhone;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Заявка «Стать приютом-партнёром» и её повторная отправка после отказа
 * (docs/product/user-flows.md, 6; поля — docs/product/data-requirements.md, 2).
 * Кто может подать — ShelterPolicy: register (нет приюта) или resubmit (свой отклонённый).
 */
class ShelterApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $shelter = $user->shelter();

        return $shelter === null
            ? $user->can('register', Shelter::class)
            : $user->can('resubmit', $shelter);
    }

    protected function prepareForValidation(): void
    {
        $binIin = preg_replace('/\s+/', '', (string) $this->input('bin_iin'));

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'city' => trim((string) $this->input('city')),
            'address' => trim((string) $this->input('address')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'website' => trim((string) $this->input('website')) ?: null,
            'bin_iin' => $binIin !== '' ? $binIin : null,
            'phone' => Phone::normalize($this->input('phone')) ?? $this->input('phone'),
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
            'bin_iin' => ['nullable', 'string', new BinIin],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'agree' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // У приюта может быть и городской номер: +7 727…, +7 717…
            'phone' => 'Укажите казахстанский номер, например +7 727 123 45 67 или +7 701 234 56 78.',
            'agree.accepted' => 'Нужно согласиться с правилами площадки.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Название приюта',
            'email' => 'Эл. почта приюта',
            'phone' => 'Телефон приюта',
        ];
    }
}
