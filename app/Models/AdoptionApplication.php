<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use Database\Factories\AdoptionApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Заявка «Хочу забрать» / «Взять под опеку».
 *
 * @property ApplicationType $type
 * @property ApplicationStatus $status
 */
#[Fillable([
    'pet_id', 'user_id', 'type', 'contact_name', 'contact_phone', 'city', 'experience',
    'living_conditions', 'message', 'status', 'shelter_comment', 'processed_at',
])]
class AdoptionApplication extends Model
{
    /** @use HasFactory<AdoptionApplicationFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'new',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ApplicationType::class,
            'status' => ApplicationStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Pet, $this>
     */
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', ApplicationStatus::active());
    }
}
