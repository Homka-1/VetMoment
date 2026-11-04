<?php

namespace App\Http\Requests\Shelter;

use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Решение приюта по заявке: «Взять в работу», «Одобрить», «Отклонить» (с причиной), «Питомец передан».
 * Допустимость перехода — AdoptionApplications::TRANSITIONS.
 */
class ApplicationDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('process', $this->route('application'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ApplicationStatus::class)->only([
                ApplicationStatus::InReview, ApplicationStatus::Approved, ApplicationStatus::Rejected, ApplicationStatus::Completed,
            ])],
            'shelter_comment' => ['nullable', 'required_if:status,rejected', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shelter_comment.required_if' => 'Напишите причину — её увидит человек, оставивший заявку.',
        ];
    }

    public function status(): ApplicationStatus
    {
        return ApplicationStatus::from($this->validated('status'));
    }
}
