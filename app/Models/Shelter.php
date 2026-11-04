<?php

namespace App\Models;

use App\Enums\ShelterMemberRole;
use App\Enums\ShelterStatus;
use App\Models\Concerns\HasSlug;
use Database\Factories\ShelterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Приют. Публикует анкеты, сборы и слоты только после одобрения (status = approved).
 *
 * @property ShelterStatus $status
 */
#[Fillable([
    'name', 'slug', 'description', 'city', 'address', 'phone', 'email', 'website', 'bin_iin',
    'logo_path', 'walk_rules', 'status', 'moderation_comment', 'approved_at',
])]
class Shelter extends Model
{
    /** @use HasFactory<ShelterFactory> */
    use HasFactory, HasSlug;

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
            'status' => ShelterStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    protected function slugSource(): string
    {
        return $this->name.' '.$this->city;
    }

    /**
     * @return BelongsToMany<User, $this, ShelterMember>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shelter_user')
            ->using(ShelterMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this, ShelterMember>
     */
    public function owners(): BelongsToMany
    {
        return $this->members()->wherePivot('role', ShelterMemberRole::Owner->value);
    }

    /**
     * @return HasMany<Pet, $this>
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    /**
     * @return HasMany<DonationCampaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(DonationCampaign::class);
    }

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * @return HasMany<WalkSlot, $this>
     */
    public function walkSlots(): HasMany
    {
        return $this->hasMany(WalkSlot::class);
    }

    /**
     * @return HasMany<WalkTemplate, $this>
     */
    public function walkTemplates(): HasMany
    {
        return $this->hasMany(WalkTemplate::class);
    }

    public function isApproved(): bool
    {
        return $this->status === ShelterStatus::Approved;
    }

    /** Одобренные приюты — только их контент виден публично. */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', ShelterStatus::Approved);
    }
}
