<?php

namespace App\Http\Requests\Walks;

use App\Models\Pet;
use App\Models\WalkSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запись на выгул (шаг «Подтверждение»): собака (необязательно), комментарий, 18+ и правила приюта.
 * Права — WalkSlotPolicy::book; места, сроки и лимиты — WalkBookingService.
 */
class WalkBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('book', $this->slot());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pet_id' => $this->input('pet_id') ?: null,
            'comment' => trim((string) $this->input('comment')) ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pet_id' => ['nullable', 'integer', Rule::exists('pets', 'id')->where('shelter_id', $this->slot()->shelter_id)],
            'comment' => ['nullable', 'string', 'max:500'],
            'adult' => ['accepted'],
            'rules' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pet_id.exists' => 'Выберите собаку из списка.',
            'adult.accepted' => 'Гулять с собаками приюта можно с 18 лет — подтвердите возраст.',
            'rules.accepted' => 'Прочитайте правила приюта и отметьте, что согласны с ними.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['comment' => 'комментарий'];
    }

    public function slot(): WalkSlot
    {
        return $this->route('slot');
    }

    public function pet(): ?Pet
    {
        $id = $this->validated('pet_id');

        return $id ? Pet::query()->find($id) : null;
    }
}
