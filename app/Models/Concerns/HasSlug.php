<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Автоматический уникальный slug (латиница) из поля-источника при создании.
 *
 * Модель задаёт `slugSource(): string` (значение для транслитерации) и,
 * при желании, `slugWithSuffix(): bool` — добавлять ли случайный суффикс («barsik-7f3a»).
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->slug)) {
                $model->slug = $model->generateUniqueSlug();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function generateUniqueSlug(): string
    {
        $base = Str::slug(Str::limit($this->slugSource(), 60, ''), language: 'ru') ?: 'item';

        if ($this->slugWithSuffix()) {
            do {
                $slug = $base.'-'.Str::lower(Str::random(4));
            } while (static::query()->where('slug', $slug)->exists());

            return $slug;
        }

        $slug = $base;
        $i = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    abstract protected function slugSource(): string;

    protected function slugWithSuffix(): bool
    {
        return false;
    }
}
