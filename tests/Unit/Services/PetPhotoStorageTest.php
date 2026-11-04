<?php

namespace Tests\Unit\Services;

use App\Services\PetPhotoStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\Concerns\MakesImages;
use Tests\TestCase;

/**
 * Ресайз фото питомца: 1600 px + превью 600 px; без GD — исходный файл.
 */
class PetPhotoStorageTest extends TestCase
{
    use MakesImages;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    #[RequiresPhpExtension('gd')]
    public function test_large_photo_is_resized_and_gets_thumbnail(): void
    {
        $image = imagecreatetruecolor(3200, 2000);
        ob_start();
        imagejpeg($image);
        $file = UploadedFile::fake()->createWithContent('big.jpg', (string) ob_get_clean());

        $result = (new PetPhotoStorage)->store($file, 7);

        $this->assertStringStartsWith('pets/7/', $result['path']);
        $this->assertNotNull($result['thumb_path']);
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($result['path']));
        $this->assertSame([1600, 1000], [$w, $h]);
        [$w] = getimagesizefromstring(Storage::disk('public')->get($result['thumb_path']));
        $this->assertSame(600, $w);
    }

    public function test_small_or_unprocessable_photo_is_kept(): void
    {
        $result = (new PetPhotoStorage)->store($this->png(), 3);

        $this->assertStringStartsWith('pets/3/', $result['path']);
        Storage::disk('public')->assertExists($result['path']);
    }
}
