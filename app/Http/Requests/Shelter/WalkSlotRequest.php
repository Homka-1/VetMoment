<?php

namespace App\Http\Requests\Shelter;

use App\Models\Shelter;
use App\Models\WalkSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Разовый слот выгула (docs/product/data-requirements.md, 7): дата, начало и конец по времени Казахстана,
 * длительность 30–180 мин с шагом 15 мин, 1–20 мест, не в прошлом и не дальше 8 недель, собаки своего приюта.
 */
class WalkSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        $slot = $this->route('slot');

        return $slot instanceof WalkSlot
            ? $this->user()->can('update', $slot)
            : $this->user()->can('create', WalkSlot::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'meeting_point' => trim((string) $this->input('meeting_point')) ?: null,
            'pets' => array_values(array_filter((array) $this->input('pets', []))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'capacity' => ['required', 'integer', 'min:1', 'max:'.config('walks.max_capacity', 20)],
            'meeting_point' => ['nullable', 'string', 'max:255'],
            'pets' => ['array'],
            'pets.*' => ['integer', Rule::exists('pets', 'id')->where('shelter_id', $this->shelter()->id)->where('can_walk', true)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['date', 'start_time', 'end_time'])) {
                    return;
                }

                foreach (self::timeErrors($this->startsAt(), $this->endsAt()) as $field => $message) {
                    $validator->errors()->add($field, $message);
                }
            },
        ];
    }

    /**
     * Общие правила времени слота — и для разового слота, и для шаблона.
     *
     * @return array<string, string>
     */
    public static function timeErrors(Carbon $startsAt, Carbon $endsAt): array
    {
        $min = (int) config('walks.min_duration_minutes', 30);
        $max = (int) config('walks.max_duration_minutes', 180);
        $step = (int) config('walks.time_step_minutes', 15);
        $weeks = (int) config('walks.max_weeks_ahead', 8);
        $duration = (int) $startsAt->diffInMinutes($endsAt, false);
        $errors = [];

        if ($startsAt->minute % $step !== 0 || $endsAt->minute % $step !== 0) {
            $errors['start_time'] = "Время — с шагом {$step} минут: 10:00, 10:15, 10:30…";
        } elseif ($duration < $min || $duration > $max) {
            $errors['end_time'] = "Прогулка длится от {$min} до {$max} минут.";
        }

        if ($startsAt->lessThanOrEqualTo(now())) {
            $errors['date'] = 'Это время уже прошло.';
        } elseif ($startsAt->greaterThan(now()->addWeeks($weeks))) {
            $errors['date'] = "Слоты можно создавать не дальше чем на {$weeks} недель вперёд.";
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pets.*.exists' => 'Выберите собак своего приюта, которые готовы гулять.',
            'start_time.date_format' => 'Введите время начала, например 10:00.',
            'end_time.date_format' => 'Введите время окончания, например 11:00.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date' => 'дата',
            'start_time' => 'начало',
            'end_time' => 'конец',
            'capacity' => 'мест',
            'meeting_point' => 'место сбора',
        ];
    }

    public function shelter(): Shelter
    {
        $slot = $this->route('slot');

        return $slot instanceof WalkSlot ? $slot->shelter : $this->user()->shelter();
    }

    public function startsAt(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $this->input('date').' '.$this->input('start_time'), config('app.display_timezone'))->startOfMinute();
    }

    public function endsAt(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $this->input('date').' '.$this->input('end_time'), config('app.display_timezone'))->startOfMinute();
    }

    /**
     * @return array{starts_at: Carbon, ends_at: Carbon, capacity: int, meeting_point: ?string, pets: list<int>}
     */
    public function slotData(): array
    {
        return [
            'starts_at' => $this->startsAt(),
            'ends_at' => $this->endsAt(),
            'capacity' => (int) $this->validated('capacity'),
            'meeting_point' => $this->validated('meeting_point'),
            'pets' => array_map('intval', $this->validated('pets', [])),
        ];
    }
}
