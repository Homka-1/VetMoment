<?php

namespace Tests\Feature\Mockup;

use App\Models\Pet;
use Database\Seeders\DemoSeeder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Этап 15: `php artisan mockup:export` выгружает сайт с демо-данными в статику для file://.
 * Полный обход с браузером — tests/browser/mockup.mjs; здесь — небольшая выгрузка и её устройство.
 */
class MockupExportTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        if (! file_exists(public_path('build/manifest.json'))) {
            $this->markTestSkipped('Нет сборки фронтенда (npm run build).');
        }
        $this->dir = storage_path('framework/testing/mockup-'.uniqid());
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_export_writes_static_pages_without_server_links(): void
    {
        $this->seed(DemoSeeder::class);

        $this->artisan('mockup:export', ['--path' => $this->dir, '--limit' => 40])->assertSuccessful();

        foreach (['index.html', 'pets.html', 'cabinet.html', 'shelter.html', 'admin.html', '404.html', 'README.txt',
            'assets/app.css', 'assets/app.js', 'assets/demo.js', 'assets/demo.css', 'assets/data.js'] as $file) {
            $this->assertFileExists($this->dir.'/'.$file);
        }

        $index = file_get_contents($this->dir.'/index.html');
        $this->assertStringContainsString('data-demo-role="guest"', $index);
        $this->assertStringContainsString('Демо-версия:', $index);
        $this->assertStringContainsString('<script src="assets/demo.js"></script>', $index);
        $this->assertStringNotContainsString('type="module"', $index, 'модули не работают по file://');
        $this->assertStringNotContainsString('/build/', $index);
        $this->assertStringNotContainsString('name="_token"', $index);
        $this->assertDoesNotMatchRegularExpression('/(href|src|action)="(\/|https?:\/\/localhost)/', $index, 'ссылки — только относительные');

        $this->assertStringContainsString('data-demo-role="volunteer"', file_get_contents($this->dir.'/cabinet.html'));
        $this->assertStringContainsString('data-demo-action="logout"', file_get_contents($this->dir.'/cabinet.html'));
        $this->assertStringNotContainsString('url(/build/', file_get_contents($this->dir.'/assets/app.css'));

        $data = file_get_contents($this->dir.'/assets/data.js');
        $this->assertStringStartsWith('/*', $data);
        $json = json_decode(substr($data, strpos($data, '{'), strrpos($data, '}') - strpos($data, '{') + 1), true);
        $this->assertSame(['guest', 'volunteer', 'shelter', 'admin'], array_keys($json['roles']));
        $this->assertSame('cabinet.html', $json['roles']['volunteer']['home']);
        $this->assertNotEmpty($json['pets']);
        $this->assertNotEmpty($json['campaigns']);
        $this->assertArrayHasKey('header', $json['chrome']['volunteer']);
        $this->assertSame('index.html', $json['routes']['']);
    }

    public function test_catalog_shows_all_pets_on_one_page(): void
    {
        $this->seed(DemoSeeder::class);

        $this->artisan('mockup:export', ['--path' => $this->dir, '--limit' => 3])->assertSuccessful();

        $html = file_get_contents($this->dir.'/pets.html');
        $count = Pet::query()->inCatalog()->count();
        $this->assertGreaterThan(12, $count, 'в демо-данных больше одной страницы каталога');
        $this->assertSame($count, substr_count($html, 'data-demo-action="pets/'), 'каждая анкета — с кнопкой избранного');
        $this->assertStringNotContainsString('data-catalog-pagination', $html);
        $this->assertStringContainsString('data-catalog-empty', $html);
    }
}
