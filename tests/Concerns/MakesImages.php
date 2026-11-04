<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;

/**
 * Настоящие картинки для загрузок — без UploadedFile::fake()->image(), которому нужен GD
 * (в php:8.4-cli его нет).
 */
trait MakesImages
{
    /** PNG 1×1. */
    protected function png(string $name = 'photo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
    }
}
