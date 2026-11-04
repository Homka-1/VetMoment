<?php

namespace App\Http\Requests\Pets;

use App\Enums\ApplicationType;
use App\Rules\KazakhPhone;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Заявка «Забрать домой» / «Взять под опеку» (docs/product/user-flows.md, 5).
 * Кто может подать — PetPolicy::apply; повторная активная заявка — AdoptionApplications::submit.
 */
class AdoptionApplicationRequest extends FormRequest
{
    /** Варианты поля «Жильё» (только для заявки «Забрать домой»). */
    public const HOUSING = [
        'Квартира',
        'Квартира, есть балкон',
        'Частный дом с двором',
        'Снимаю жильё, хозяин не против животных',
        'Другое — расскажу в анкете',
    ];

    public function authorize(): bool
    {
        return $this->user()->can('apply', $this->route('pet'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contact_name' => trim((string) $this->input('contact_name')),
            'contact_phone' => Phone::normalize($this->input('contact_phone')) ?? $this->input('contact_phone'),
            'city' => trim((string) $this->input('city')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ApplicationType::class)],
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_phone' => ['required', 'string', new KazakhPhone],
            'city' => ['required', 'string', 'max:100'],
            'living_conditions' => ['nullable', 'required_if:type,adoption', 'string', Rule::in(self::HOUSING)],
            'experience' => ['nullable', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:2000'],
            'agree' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'living_conditions.required_if' => 'Расскажите, где будет жить питомец.',
            'agree.accepted' => 'Нужно согласие на обработку персональных данных.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'Что вы хотите',
            'contact_name' => 'Имя',
            'contact_phone' => 'Телефон',
            'city' => 'Город',
            'living_conditions' => 'Жильё',
            'experience' => 'Рассказ о себе',
            'message' => 'Комментарий',
        ];
    }
}
