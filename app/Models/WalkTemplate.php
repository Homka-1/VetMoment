<?php

namespace App\Models;

use Database\Factories\WalkTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Шаблон слотов на неделю. Дни недели — битовая маска: Пн = 1, Вт = 2, Ср = 4, … Вс = 64
 * (бит = 1 << (ISO-номер дня − 1)). Время начала — местное (Asia/Almaty).
 */
#[Fillable([
    'shelter_id', 'weekdays', 'start_time', 'duration_minutes', 'capacity', 'meeting_point',
    'valid_from', 'valid_until',
])]
class WalkTemplate extends Model
{
    /** @use HasFactory<WalkTemplateFactory> */
    use HasFactory;

    public const WEEKDAY_LABELS = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekdays' => 'integer',
            'duration_minutes' => 'integer',
            'capacity' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    /**
     * Маска из ISO-номеров дней (1 = Пн … 7 = Вс).
     *
     * @param  iterable<int>  $isoDays
     */
    public static function maskFromDays(iterable $isoDays): int
    {
        $mask = 0;

        foreach ($isoDays as $day) {
            if ($day < 1 || $day > 7) {
                throw new \InvalidArgumentException("Номер дня недели должен быть от 1 до 7, получено: {$day}");
            }

            $mask |= 1 << ($day - 1);
        }

        return $mask;
    }

    public function hasWeekday(int $isoDay): bool
    {
        return ($this->weekdays & (1 << ($isoDay - 1))) !== 0;
    }

    /**
     * ISO-номера дней, входящих в шаблон.
     *
     * @return list<int>
     */
    public function weekdayList(): array
    {
        return array_values(array_filter(range(1, 7), fn (int $day): bool => $this->hasWeekday($day)));
    }

    /**
     * @return BelongsTo<Shelter, $this>
     */
    public function shelter(): BelongsTo
    {
        return $this->belongsTo(Shelter::class);
    }

    /**
     * @return BelongsToMany<Pet, $this>
     */
    public function pets(): BelongsToMany
    {
        return $this->belongsToMany(Pet::class, 'walk_template_pet');
    }

    /**
     * @return HasMany<WalkSlot, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(WalkSlot::class, 'template_id');
    }
}
