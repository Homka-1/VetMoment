<?php

namespace App\Models;

use Database\Factories\PetPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Фото питомца. Файл — на диске `public`; ровно одна обложка (is_cover) на питомца.
 * `path` — до 1600 px по длинной стороне, `thumb_path` — превью 600 px (может быть пустым).
 */
#[Fillable(['pet_id', 'path', 'thumb_path', 'sort', 'is_cover'])]
class PetPhoto extends Model
{
    /** @use HasFactory<PetPhotoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_cover' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Pet, $this>
     */
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /** Превью для карточек и миниатюр; без превью — основной файл. */
    public function thumbUrl(): string
    {
        return Storage::disk('public')->url($this->thumb_path ?? $this->path);
    }

    /** Удаляет файлы фото с диска (запись удаляется отдельно). */
    public function deleteFiles(): void
    {
        Storage::disk('public')->delete(array_filter([$this->path, $this->thumb_path]));
    }
}
