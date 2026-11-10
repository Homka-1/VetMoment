<?php

namespace App\Http\Requests\Shelter;

use App\Models\DonationCampaign;
use App\Models\Shelter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Сбор в кабинете приюта (поля — docs/product/data-requirements.md, 5).
 * Цель 5 000 ₸ – 50 000 000 ₸; дата окончания — не раньше завтра и не позже года от старта
 * (пусто — бессрочный до достижения цели). Запуск (черновик → «Идёт сбор») — только у одобренного приюта.
 */
class CampaignRequest extends FormRequest
{
    public const GOAL_MIN = 5_000;

    public const GOAL_MAX = 50_000_000;

    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        return $campaign instanceof DonationCampaign
            ? $this->user()->can('update', $campaign)
            : $this->user()->can('create', DonationCampaign::class);
    }

    protected function prepareForValidation(): void
    {
        $goal = preg_replace('/[\s\x{00A0}₸]/u', '', (string) $this->input('goal'));

        $this->merge([
            'title' => trim((string) $this->input('title')),
            'short_description' => trim((string) $this->input('short_description')),
            'goal' => $goal === '' ? null : $goal,
            'pet_id' => $this->input('pet_id') ?: null,
            'ends_at' => $this->input('ends_at') ?: null,
            'allow_overfunding' => $this->boolean('allow_overfunding'),
            'remove_cover' => $this->boolean('remove_cover'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $starts = $this->campaign()?->starts_at ?? now();

        return [
            'title' => ['required', 'string', 'max:120'],
            'short_description' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'min:50', 'max:10000'],
            'goal' => ['required', 'integer', 'min:'.self::GOAL_MIN, 'max:'.self::GOAL_MAX],
            'ends_at' => ['nullable', 'date_format:Y-m-d', 'after:today',
                'before_or_equal:'.$starts->copy()->addYear()->format('Y-m-d')],
            'pet_id' => ['nullable', 'integer', Rule::exists('pets', 'id')->where('shelter_id', $this->shelter()->id)],
            'allow_overfunding' => ['boolean'],
            'cover' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_cover' => ['boolean'],
            'action' => ['nullable', Rule::in(['draft', 'publish', 'save'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->wantsPublish() && $this->user()->cannot('publish', $this->campaign() ?? new DonationCampaign(['shelter_id' => $this->shelter()->id]))) {
                    $validator->errors()->add('action', 'Запустить сбор можно после того, как приют пройдёт проверку. Пока сохраните черновик.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'goal.min' => 'Цель — от 5 000 ₸.',
            'goal.max' => 'Цель — не больше 50 000 000 ₸.',
            'goal.integer' => 'Введите цель целым числом тенге, например 300 000.',
            'ends_at.after' => 'Дата окончания — не раньше завтрашнего дня.',
            'ends_at.before_or_equal' => 'Сбор длится не больше года от запуска.',
            'pet_id.exists' => 'Выберите питомца своего приюта.',
            'description.min' => 'Расскажите подробнее, на что нужны деньги: от 50 символов.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'название',
            'short_description' => 'коротко о сборе',
            'description' => 'описание',
            'goal' => 'цель',
            'ends_at' => 'дата окончания',
            'cover' => 'обложка',
        ];
    }

    public function shelter(): Shelter
    {
        return $this->campaign()?->shelter ?? $this->user()->shelter();
    }

    public function campaign(): ?DonationCampaign
    {
        $campaign = $this->route('campaign');

        return $campaign instanceof DonationCampaign ? $campaign : null;
    }

    public function wantsPublish(): bool
    {
        return $this->input('action') === 'publish';
    }

    /**
     * @return array<string, mixed>
     */
    public function campaignAttributes(): array
    {
        $endsAt = $this->validated('ends_at');

        return [
            'title' => $this->validated('title'),
            'short_description' => $this->validated('short_description'),
            'description' => $this->validated('description'),
            'goal_amount' => (int) $this->validated('goal') * 100,
            // Дата окончания — конец дня по времени Казахстана, в БД — UTC.
            'ends_at' => $endsAt
                ? Carbon::createFromFormat('Y-m-d', $endsAt, config('app.display_timezone'))->endOfDay()->startOfMinute()->utc()
                : null,
            'pet_id' => $this->validated('pet_id') ? (int) $this->validated('pet_id') : null,
            'allow_overfunding' => (bool) $this->validated('allow_overfunding'),
        ];
    }

    public function cover(): ?UploadedFile
    {
        $file = $this->file('cover');

        return $file instanceof UploadedFile ? $file : null;
    }
}
