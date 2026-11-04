<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\DonationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Пожертвование на сбор или в общий фонд приюта (campaign_id = null). Сумма — в тиынах.
 *
 * @property PaymentStatus $status
 * @property int $amount
 */
#[Fillable([
    'uuid', 'campaign_id', 'shelter_id', 'user_id', 'donor_name', 'donor_email', 'is_anonymous', 'amount',
    'comment', 'status', 'provider', 'provider_payment_id', 'idempotency_key', 'paid_at',
])]
class Donation extends Model
{
    /** @use HasFactory<DonationFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
        'provider' => 'fake',
        'is_anonymous' => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $donation): void {
            $donation->uuid ??= (string) Str::uuid();
            $donation->idempotency_key ??= (string) Str::uuid();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'is_anonymous' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<DonationCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(DonationCampaign::class, 'campaign_id');
    }

    /**
     * @return BelongsTo<Shelter, $this>
     */
    public function shelter(): BelongsTo
    {
        return $this->belongsTo(Shelter::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Имя для публичного списка: «Аноним», если донор скрыл имя или не указал его. */
    public function publicDonorName(): string
    {
        return $this->is_anonymous || blank($this->donor_name) ? 'Аноним' : $this->donor_name;
    }
}
