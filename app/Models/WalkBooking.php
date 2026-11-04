<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\CancelledBy;
use Database\Factories\WalkBookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Запись волонтёра на слот выгула.
 *
 * @property BookingStatus $status
 * @property CancelledBy|null $cancelled_by
 */
#[Fillable([
    'walk_slot_id', 'user_id', 'pet_id', 'status', 'comment', 'shelter_comment', 'cancelled_by',
    'confirmed_at', 'cancelled_at',
])]
class WalkBooking extends Model
{
    /** @use HasFactory<WalkBookingFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'cancelled_by' => CancelledBy::class,
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WalkSlot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(WalkSlot::class, 'walk_slot_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Pet, $this>
     */
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::active());
    }
}
