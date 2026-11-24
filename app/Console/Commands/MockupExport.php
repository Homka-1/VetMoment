<?php

namespace App\Console\Commands;

use App\Services\Mockup\MockupExporter;
use Illuminate\Console\Command;

/**
 * php artisan mockup:export — статический демо-макет в папке mockup/ (этап 15).
 * Нужны демо-данные (DemoSeeder) и собранный фронтенд (public/build). Папка пересоздаётся целиком.
 */
class MockupExport extends Command
{
    protected $signature = 'mockup:export
        {--path= : Куда выгрузить (по умолчанию mockup/ в корне проекта)}
        {--limit=1000 : Предел числа страниц}';

    protected $description = 'Выгрузить сайт с демо-данными в статический макет (открывается двойным кликом по index.html)';

    public function handle(MockupExporter $exporter): int
    {
        $path = $this->option('path') ?: base_path('mockup');
        if (! file_exists(public_path('build/manifest.json'))) {
            $this->error('Нет сборки фронтенда (public/build/manifest.json) — выполните npm run build.');

            return self::FAILURE;
        }

        $this->info("Выгрузка в {$path} …");
        $result = $exporter->export($path, (int) $this->option('limit'), fn (string $line) => $this->line('  '.$line, verbosity: 'v'));

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }
        $this->info(sprintf('Готово: %d страниц, %d файлов картинок, %.1f МБ. Откройте %s/index.html',
            $result['pages'], $result['assets'], $result['bytes'] / 1048576, $path));

        return self::SUCCESS;
    }
}
