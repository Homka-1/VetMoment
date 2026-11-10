<?php

namespace App\Http\Requests\Shelter;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Решение приюта по записи: «Подтвердить», «Отклонить» и «Отменить» (с причиной), «Состоялся», «Не пришёл».
 * Допустимость перехода — WalkBookingService::TRANSITIONS.
 */
class WalkBookingDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('booking'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(BookingStatus::class)->except([BookingStatus::Pending])],
            'shelter_comment' => ['nullable', 'required_if:status,rejected,cancelled', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shelter_comment.required_if' => 'Напишите причину — её увидит волонтёр.',
        ];
    }

    public function status(): BookingStatus
    {
        return BookingStatus::from($this->validated('status'));
    }
}
