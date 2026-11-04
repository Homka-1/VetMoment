<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasSlug;
use Database\Factories\DonationCampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Сбор пожертвований. Суммы — в тиынах (1 ₸ = 100 тиын).
 *
 * @property CampaignStatus $status
 * @property int $goal_amount
 * @property int $raised_amount
 * @property int $donations_count
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 */
#[Fillable([
    'shelter_id', 'pet_id', 'title', 'slug', 'short_description', 'description', 'goal_amount',
    'raised_amount', 'donations_count', 'starts_at', 'ends_at', 'allow_overfunding', 'cover_path',
    'status', 'is_hidden', 'completed_at',
])]
class DonationCampaign extends Model
{
    /** @use HasFactory<DonationCampaignFactory> */
    use HasFactory, HasSlug;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
        'raised_amount' => 0,
        'donations_count' => 0,
        'allow_overfunding' => false,
        'is_hidden' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'goal_amount' => 'integer',
            'raised_amount' => 'integer',
            'donations_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'allow_overfunding' => 'boolean',
            'is_hidden' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    protected function slugSource(): string
    {
        return $this->title;
    }

    /**
     * @return BelongsTo<Shelter, $this>
     */
    public function shelter(): BelongsTo
    {
        return $this->belongsTo(Shelter::class);
    }

    /**
     * @return BelongsTo<Pet, $this>
     */
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'campaign_id');
    }

    /**
     * @return HasMany<CampaignReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(CampaignReport::class, 'campaign_id')->latest('published_at');
    }

    /** Прогресс для полосы, 0–100 (текстом показывается реальная сумма). */
    public function progressPercent(): int
    {
        if ($this->goal_amount <= 0) {
            return 0;
        }

        return (int) min(100, floor($this->raised_amount * 100 / $this->goal_amount));
    }

    /**
     * Пересчитывает денормализованные raised_amount и donations_count по успешным пожертвованиям.
     * Вызывать в транзакции вместе со сменой статуса платежа (этап 9).
     */
    public function recalculateTotals(): void
    {
        $totals = $this->donations()
            ->where('status', PaymentStatus::Succeeded)
            ->selectRaw('COALESCE(SUM(amount), 0) AS total, COUNT(*) AS cnt')
            ->toBase()
            ->first();

        $this->forceFill([
            'raised_amount' => (int) $totals->total,
            'donations_count' => (int) $totals->cnt,
        ])->save();
    }

    /** Видимые публично: публичный статус, не скрыт, приют одобрен. */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->whereIn('status', CampaignStatus::public())
            ->where('is_hidden', false)
            ->whereHas('shelter', fn (Builder $q) => $q->approved());
    }
}
