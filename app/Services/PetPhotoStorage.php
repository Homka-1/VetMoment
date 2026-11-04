<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Сохранение фото питомца на диск `public` (docs/product/data-requirements.md, 3):
 * основной файл — до 1600 px по длинной стороне, превью — 600 px, формат webp (или jpeg, если GD без webp).
 *
 * Если расширения GD нет (локальный PHP без него) или файл не удаётся декодировать,
 * сохраняется исходный файл без превью — сайт работает, просто фото тяжелее.
 */
class PetPhotoStorage
{
    public const MAX_SIDE = 1600;

    public const THUMB_SIDE = 600;

    private const QUALITY = 82;

    /**
     * @return array{path: string, thumb_path: string|null}
     */
    public function store(UploadedFile $file, int $petId): array
    {
        $directory = "pets/{$petId}";
        $name = (string) Str::uuid();

        $image = $this->canResize() ? @imagecreatefromstring((string) file_get_contents($file->getRealPath())) : false;

        if ($image === false) {
            $path = $file->storeAs($directory, $name.'.'.$this->extension($file), 'public');

            return ['path' => $path, 'thumb_path' => null];
        }

        $image = $this->orient($image, $file);
        $format = function_exists('imagewebp') ? 'webp' : 'jpg';

        $path = "{$directory}/{$name}.{$format}";
        $thumb = "{$directory}/{$name}-600.{$format}";

        Storage::disk('public')->put($path, $this->encode($this->fit($image, self::MAX_SIDE), $format));
        Storage::disk('public')->put($thumb, $this->encode($this->fit($image, self::THUMB_SIDE), $format));

        imagedestroy($image);

        return ['path' => $path, 'thumb_path' => $thumb];
    }

    public function canResize(): bool
    {
        return function_exists('imagecreatefromstring') && function_exists('imagescale');
    }

    /** Уменьшает изображение до $side по длинной стороне (не увеличивает). */
    private function fit(\GdImage $image, int $side): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $side / max($width, $height));

        if ($scale >= 1) {
            return $image;
        }

        $resized = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)), IMG_BICUBIC);

        return $resized === false ? $image : $resized;
    }

    private function encode(\GdImage $image, string $format): string
    {
        ob_start();
        $format === 'webp' ? imagewebp($image, null, self::QUALITY) : imagejpeg($image, null, self::QUALITY);

        return (string) ob_get_clean();
    }

    /** Фото с телефона: поворот по EXIF, иначе портрет ляжет на бок. */
    private function orient(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || ! in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1);
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated === false ? $image : $rotated;
    }

    private function extension(UploadedFile $file): string
    {
        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension());

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }
}
