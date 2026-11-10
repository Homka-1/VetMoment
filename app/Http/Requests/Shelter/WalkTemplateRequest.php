<?php

namespace App\Http\Requests\Shelter;

use App\Models\WalkSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Шаблон недели: дни × время × длительность × места × собаки на период до 8 недель вперёд.
 */
class WalkTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', WalkSlot::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'meeting_point' => trim((string) $this->input('meeting_point')) ?: null,
            'pets' => array_values(array_filter((array) $this->input('pets', []))),
            'weekdays' => array_values(array_filter((array) $this->input('weekdays', []))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $weeks = (int) config('walks.max_weeks_ahead', 8);
        $tz = config('app.display_timezone');

        return [
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7', 'distinct'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', Rule::in(self::durations())],
            'capacity' => ['required', 'integer', 'min:1', 'max:'.config('walks.max_capacity', 20)],
            'meeting_point' => ['nullable', 'string', 'max:255'],
            'valid_from' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.Carbon::today($tz)->toDateString()],
            'valid_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:valid_from',
                'before_or_equal:'.Carbon::today($tz)->addWeeks($weeks)->toDateString()],
            'pets' => ['array'],
            'pets.*' => ['integer', Rule::exists('pets', 'id')->where('shelter_id', $this->user()->shelter()?->id)->where('can_walk', true)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('start_time')) {
                    return;
                }

                $step = (int) config('walks.time_step_minutes', 15);
                if ((int) substr((string) $this->input('start_time'), 3, 2) % $step !== 0) {
                    $validator->errors()->add('start_time', "Время — с шагом {$step} минут: 10:00, 10:15, 10:30…");
                }
            },
        ];
    }

    /**
     * Длительности для списка: 30, 45 … 180 минут.
     *
     * @return list<int>
     */
    public static function durations(): array
    {
        return range(
            (int) config('walks.min_duration_minutes', 30),
            (int) config('walks.max_duration_minutes', 180),
            (int) config('walks.time_step_minutes', 15),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $weeks = (int) config('walks.max_weeks_ahead', 8);

        return [
            'weekdays.required' => 'Отметьте хотя бы один день недели.',
            'valid_from.after_or_equal' => 'Период не может начинаться в прошлом.',
            'valid_until.after_or_equal' => 'Дата окончания — не раньше даты начала.',
            'valid_until.before_or_equal' => "Шаблон действует не дальше чем на {$weeks} недель вперёд.",
            'pets.*.exists' => 'Выберите собак своего приюта, которые готовы гулять.',
            'start_time.date_format' => 'Введите время начала, например 10:00.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'start_time' => 'начало',
            'duration_minutes' => 'длительность',
            'capacity' => 'мест',
            'meeting_point' => 'место сбора',
            'valid_from' => 'с какого дня',
            'valid_until' => 'по какой день',
        ];
    }

    /**
     * @return array{weekdays: list<int>, start_time: string, duration_minutes: int, capacity: int, meeting_point: ?string, valid_from: Carbon, valid_until: Carbon, pets: list<int>}
     */
    public function templateData(): array
    {
        $tz = config('app.display_timezone');

        return [
            'weekdays' => array_map('intval', $this->validated('weekdays')),
            'start_time' => $this->validated('start_time'),
            'duration_minutes' => (int) $this->validated('duration_minutes'),
            'capacity' => (int) $this->validated('capacity'),
            'meeting_point' => $this->validated('meeting_point'),
            'valid_from' => Carbon::createFromFormat('Y-m-d', $this->validated('valid_from'), $tz)->startOfDay(),
            'valid_until' => Carbon::createFromFormat('Y-m-d', $this->validated('valid_until'), $tz)->startOfDay(),
            'pets' => array_map('intval', $this->validated('pets', [])),
        ];
    }
}
