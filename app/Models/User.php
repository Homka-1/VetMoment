<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\ShelterMemberRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Пользователь: волонтёр, представитель приюта или администратор (одна роль).
 *
 * @property Role $role
 */
#[Fillable(['login', 'name', 'phone', 'email', 'password', 'role', 'avatar_path', 'is_blocked', 'birth_year'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var array<string, mixed> */
    protected $attributes = [
        'role' => 'volunteer',
        'is_blocked' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_blocked' => 'boolean',
            'birth_year' => 'integer',
        ];
    }

    /**
     * Приют(ы) пользователя. В MVP — не более одного (shelter_user.user_id уникален), см. shelter().
     *
     * @return BelongsToMany<Shelter, $this, ShelterMember>
     */
    public function shelters(): BelongsToMany
    {
        return $this->belongsToMany(Shelter::class, 'shelter_user')
            ->using(ShelterMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** Приют, в котором работает пользователь (или null). */
    public function shelter(): ?Shelter
    {
        return $this->shelters->first();
    }

    /**
     * @return BelongsToMany<Pet, $this>
     */
    public function favoritePets(): BelongsToMany
    {
        return $this->belongsToMany(Pet::class, 'favorites')->withTimestamps();
    }

    /**
     * @return HasMany<AdoptionApplication, $this>
     */
    public function adoptionApplications(): HasMany
    {
        return $this->hasMany(AdoptionApplication::class);
    }

    /**
     * @return HasMany<WalkBooking, $this>
     */
    public function walkBookings(): HasMany
    {
        return $this->hasMany(WalkBooking::class);
    }

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isShelterStaff(): bool
    {
        return $this->role === Role::ShelterStaff;
    }

    /** Сотрудник (любой роли внутри) именно этого приюта. */
    public function worksAt(int|Shelter|null $shelter): bool
    {
        $shelterId = $shelter instanceof Shelter ? $shelter->getKey() : $shelter;

        return $shelterId !== null
            && $this->isShelterStaff()
            && $this->shelter()?->getKey() === $shelterId;
    }

    /** Владелец этого приюта (управляет профилем и сотрудниками). */
    public function ownsShelter(Shelter $shelter): bool
    {
        return $this->worksAt($shelter)
            && $this->shelter()?->pivot?->role === ShelterMemberRole::Owner;
    }

    /** Имя маршрута «домой» после входа — кабинет по роли. */
    public function homeRoute(): string
    {
        return match ($this->role) {
            Role::Admin => 'admin.dashboard',
            Role::ShelterStaff => 'shelter.dashboard',
            Role::Volunteer => 'cabinet.dashboard',
        };
    }
}
