<?php

namespace App\Models;

use App\Enums\PetSize;
use App\Enums\PetStatus;
use App\Enums\Sex;
use App\Enums\Species;
use App\Models\Concerns\HasSlug;
use App\Support\Plural;
use Database\Factories\PetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Анкета питомца.
 *
 * @property Species $species
 * @property Sex $sex
 * @property PetSize $size
 * @property PetStatus $status
 * @property Carbon|null $birth_date
 * @property Carbon|null $arrived_at
 */
#[Fillable([
    'shelter_id', 'name', 'slug', 'species', 'sex', 'birth_date', 'birth_date_is_approx', 'size', 'breed', 'color',
    'character', 'good_with_kids', 'good_with_cats', 'good_with_dogs', 'house_trained', 'leash_trained',
    'vaccinated', 'sterilized', 'chipped', 'health_notes', 'story', 'status', 'can_walk',
    'guardianship_available', 'arrived_at', 'adopted_at', 'is_hidden',
])]
class Pet extends Model
{
    /** @use HasFactory<PetFactory> */
    use HasFactory, HasSlug;

    /** Черты характера — bool-колонки; подписи для интерфейса. */
    public const TRAITS = [
        'good_with_kids' => 'Ладит с детьми',
        'good_with_cats' => 'Ладит с кошками',
        'good_with_dogs' => 'Ладит с собаками',
        'house_trained' => 'Приучен к дому',
        'leash_trained' => 'Ходит на поводке',
    ];

    /** Подписи черт, когда флажок не отмечен, — для списка «Характер» на карточке. */
    public const TRAITS_NEGATIVE = [
        'good_with_kids' => 'С детьми — осторожно',
        'good_with_cats' => 'С кошками — осторожно',
        'good_with_dogs' => 'С другими собаками — осторожно',
        'house_trained' => 'Пока учится порядку в доме',
        'leash_trained' => 'Учится ходить на поводке',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
        'birth_date_is_approx' => false,
        'is_hidden' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'species' => Species::class,
            'sex' => Sex::class,
            'size' => PetSize::class,
            'status' => PetStatus::class,
            'birth_date' => 'date',
            'birth_date_is_approx' => 'boolean',
            'good_with_kids' => 'boolean',
            'good_with_cats' => 'boolean',
            'good_with_dogs' => 'boolean',
            'house_trained' => 'boolean',
            'leash_trained' => 'boolean',
            'vaccinated' => 'boolean',
            'sterilized' => 'boolean',
            'chipped' => 'boolean',
            'can_walk' => 'boolean',
            'guardianship_available' => 'boolean',
            'arrived_at' => 'date',
            'adopted_at' => 'datetime',
            'is_hidden' => 'boolean',
        ];
    }

    protected function slugSource(): string
    {
        return $this->name;
    }

    protected function slugWithSuffix(): bool
    {
        return true;
    }

    /**
     * @return BelongsTo<Shelter, $this>
     */
    public function shelter(): BelongsTo
    {
        return $this->belongsTo(Shelter::class);
    }

    /**
     * @return HasMany<PetPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(PetPhoto::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * @return HasOne<PetPhoto, $this>
     */
    public function cover(): HasOne
    {
        return $this->hasOne(PetPhoto::class)->where('is_cover', true);
    }

    /**
     * @return HasMany<AdoptionApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(AdoptionApplication::class);
    }

    /**
     * @return HasMany<DonationCampaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(DonationCampaign::class);
    }

    /**
     * @return BelongsToMany<WalkSlot, $this>
     */
    public function walkSlots(): BelongsToMany
    {
        return $this->belongsToMany(WalkSlot::class, 'walk_slot_pet');
    }

    /**
     * @return HasMany<WalkBooking, $this>
     */
    public function walkBookings(): HasMany
    {
        return $this->hasMany(WalkBooking::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * Возраст в полных месяцах (null, если дата рождения неизвестна).
     * Подпись со склонением — ageLabel().
     */
    public function ageInMonths(?Carbon $at = null): ?int
    {
        if ($this->birth_date === null) {
            return null;
        }

        return (int) $this->birth_date->diffInMonths($at ?? now(), absolute: true);
    }

    /** «2 года», «5 месяцев»; null, если дата рождения неизвестна. */
    public function ageLabel(?Carbon $at = null): ?string
    {
        $months = $this->ageInMonths($at);

        return $months === null ? null : Plural::age($months);
    }

    /** Строка под кличкой на карточке: «Собака, 2 года, мальчик». */
    public function summary(): string
    {
        $parts = [$this->species->label(), $this->ageLabel()];

        if ($this->sex !== Sex::Unknown) {
            $parts[] = mb_strtolower($this->sex->label());
        }

        return implode(', ', array_filter($parts));
    }

    /**
     * Включённые черты характера: ключ → подпись.
     *
     * @return array<string, string>
     */
    public function traitLabels(): array
    {
        return array_filter(self::TRAITS, fn (string $label, string $key): bool => (bool) $this->{$key}, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Все черты для карточки: подпись и есть ли черта.
     *
     * @return list<array{label: string, value: bool}>
     */
    public function characterList(): array
    {
        $list = [];

        foreach (self::TRAITS as $key => $label) {
            $list[] = ['label' => $this->{$key} ? $label : self::TRAITS_NEGATIVE[$key], 'value' => (bool) $this->{$key}];
        }

        return $list;
    }

    /**
     * Здоровье для карточки: иконка, подпись, отмечено ли.
     *
     * @return list<array{icon: string, label: string, value: bool}>
     */
    public function healthList(): array
    {
        $sterilized = match ($this->sex) {
            Sex::Male => ['Кастрирован', 'Не кастрирован'],
            Sex::Female => ['Стерилизована', 'Не стерилизована'],
            default => ['Стерилизован', 'Не стерилизован'],
        };

        return [
            ['icon' => 'syringe', 'label' => $this->vaccinated ? 'Привит' : 'Ещё не привит', 'value' => (bool) $this->vaccinated],
            ['icon' => 'shield-check', 'label' => $sterilized[$this->sterilized ? 0 : 1], 'value' => (bool) $this->sterilized],
            ['icon' => 'tag', 'label' => $this->chipped ? 'Чипирован' : 'Без чипа', 'value' => (bool) $this->chipped],
        ];
    }

    /** «Собака, 2 года, мальчик, средний» — подзаголовок карточки питомца. */
    public function fullSummary(): string
    {
        $age = $this->ageLabel();
        if ($age !== null && $this->birth_date_is_approx) {
            $age = '≈ '.$age;
        }

        $parts = [$this->species->label(), $age];
        if ($this->sex !== Sex::Unknown) {
            $parts[] = mb_strtolower($this->sex->label());
        }
        $parts[] = mb_strtolower($this->size->label());

        return implode(', ', array_filter($parts));
    }

    /** Подпись фото по умолчанию (data-requirements, доступность): «Бублик — собака, фото 2». */
    public function photoAlt(int $position): string
    {
        return "{$this->name} — ".mb_strtolower($this->species->label()).", фото {$position}";
    }

    /** С питомцем можно записаться на выгул (правило W8). */
    public function isWalkable(): bool
    {
        return $this->can_walk && in_array($this->status, PetStatus::walkable(), true);
    }

    /**
     * Видимые публично: публичный статус, не скрыт модератором, приют одобрен.
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->whereIn('status', PetStatus::public())
            ->where('is_hidden', false)
            ->whereHas('shelter', fn (Builder $q) => $q->approved());
    }

    /** Основной каталог: видимые и ещё ищут дом (без «Нашёл дом»). */
    #[Scope]
    protected function inCatalog(Builder $query): void
    {
        $query->visible()->whereIn('status', PetStatus::catalog());
    }

    /** Готовы к прогулке (W8): отмечены «можно на выгул» и в подходящем статусе. */
    #[Scope]
    protected function walkable(Builder $query): void
    {
        $query->where('can_walk', true)->whereIn('status', PetStatus::walkable());
    }
}
