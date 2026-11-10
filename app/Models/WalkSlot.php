<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\WalkSlotState;
use App\Enums\WalkSlotStatus;
use Database\Factories\WalkSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Слот выгула. Время — UTC в БД. В БД хранится только open/cancelled,
 * «заполнен» и «прошёл» вычисляются (state()).
 *
 * @property WalkSlotStatus $status
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int $capacity
 * @property int $booked_count
 */
#[Fillable([
    'shelter_id', 'template_id', 'starts_at', 'ends_at', 'capacity', 'booked_count', 'meeting_point', 'status',
])]
class WalkSlot extends Model
{
    /** @use HasFactory<WalkSlotFactory> */
    use HasFactory;

    /** «Мало мест» — когда свободно столько или меньше (и кто-то уже записан). */
    public const FEW_SPOTS_LEFT = 2;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'open',
        'booked_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WalkSlotStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
            'booked_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Shelter, $this>
     */
    public function shelter(): BelongsTo
    {
        return $this->belongsTo(Shelter::class);
    }

    /**
     * @return BelongsTo<WalkTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WalkTemplate::class, 'template_id');
    }

    /**
     * Питомцы, с которыми можно погулять в этом слоте; пусто — «питомца назначит приют».
     *
     * @return BelongsToMany<Pet, $this>
     */
    public function pets(): BelongsToMany
    {
        return $this->belongsToMany(Pet::class, 'walk_slot_pet');
    }

    /**
     * @return HasMany<WalkBooking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(WalkBooking::class);
    }

    /**
     * @return HasMany<WalkBooking, $this>
     */
    public function activeBookings(): HasMany
    {
        return $this->bookings()->whereIn('status', BookingStatus::active());
    }

    public function spotsLeft(): int
    {
        return max(0, $this->capacity - $this->booked_count);
    }

    public function durationMinutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at, absolute: true);
    }

    /** Вычисляемое состояние для интерфейса. */
    public function state(?Carbon $now = null): WalkSlotState
    {
        $now ??= now();

        return match (true) {
            $this->status === WalkSlotStatus::Cancelled => WalkSlotState::Cancelled,
            $this->starts_at->lessThanOrEqualTo($now) => WalkSlotState::Past,
            $this->spotsLeft() === 0 => WalkSlotState::Full,
            $this->starts_at->lessThan(self::bookingClosesAt($now)) => WalkSlotState::Closed,
            $this->booked_count > 0 && $this->spotsLeft() <= self::FEW_SPOTS_LEFT => WalkSlotState::FewLeft,
            default => WalkSlotState::Available,
        };
    }

    /** Слоты, начинающиеся раньше этого момента, уже закрыты для записи (W1). */
    public static function bookingClosesAt(?Carbon $now = null): Carbon
    {
        return ($now ?? now())->copy()->addHours((int) config('walks.book_before_hours', 2));
    }

    /** Граница горизонта записи (W2): слоты позже этого момента волонтёрам не показываются. */
    public static function horizonEndsAt(?Carbon $now = null): Carbon
    {
        return ($now ?? now())->copy()->addDays((int) config('walks.horizon_days', 14));
    }

    /** Пересекается ли по времени с другим интервалом (W5). */
    public function overlaps(self $other): bool
    {
        return $this->starts_at->lessThan($other->ends_at) && $this->ends_at->greaterThan($other->starts_at);
    }

    /** Можно ли сейчас выбрать питомца в этом слоте: слот без списка — «питомца назначит приют». */
    public function offersPet(Pet $pet): bool
    {
        return $this->relationLoaded('pets')
            ? $this->pets->contains($pet)
            : $this->pets()->whereKey($pet->getKey())->exists();
    }

    /**
     * Пересчитывает денормализованный booked_count по активным записям.
     * Сервис бронирования (этап 10) вызывает под lockForUpdate().
     */
    public function recalculateBookedCount(): void
    {
        $this->forceFill(['booked_count' => $this->activeBookings()->count()])->save();
    }

    /** Открытые слоты, которые ещё не начались. */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('status', WalkSlotStatus::Open)
            ->where('starts_at', '>', now())
            ->orderBy('starts_at');
    }
}
