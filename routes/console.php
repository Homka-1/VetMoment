<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Служебные команды для автоматического первого запуска (используются в start.sh).
*/

Artisan::command('app:wait-for-db {--timeout=60 : Сколько секунд ждать}', function () {
    $deadline = time() + (int) $this->option('timeout');

    while (true) {
        try {
            DB::connection()->getPdo();
            $this->info('База данных доступна.');

            return 0;
        } catch (Throwable $e) {
            if (time() >= $deadline) {
                $this->error('База данных недоступна: '.$e->getMessage());

                return 1;
            }

            $this->line('Ждём базу данных…');
            sleep(2);
        }
    }
})->purpose('Дождаться, пока база данных начнёт принимать подключения');

Artisan::command('app:seed-if-empty', function () {
    if (! Schema::hasTable('users')) {
        $this->error('Таблицы не созданы — сначала выполните php artisan migrate.');

        return 1;
    }

    if (DB::table('users')->exists()) {
        $this->info('В базе уже есть данные — демо-данные не загружаем.');

        return 0;
    }

    $this->info('База пустая — загружаем демо-данные.');

    return $this->call('db:seed', ['--force' => true]);
})->purpose('Заполнить базу демо-данными, если она пустая');
