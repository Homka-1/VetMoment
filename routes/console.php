<?php

use App\Services\DonationPayments;
use App\Services\WalkBookingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
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

/*
| Пожертвования (этап 9). Запускаются по расписанию — см. конец файла.
*/

Artisan::command('app:expire-pending-donations', function (DonationPayments $payments) {
    $this->info('Помечено «Срок оплаты истёк»: '.$payments->expireStale());
})->purpose('Пометить неоплаченные пожертвования старше PAYMENT_PENDING_TTL минут как истёкшие');

Artisan::command('app:close-expired-campaigns', function (DonationPayments $payments) {
    $this->info('Закрыто сборов с истёкшим сроком: '.$payments->closeExpiredCampaigns());
})->purpose('Закрыть идущие сборы, у которых прошла дата окончания');

/*
| Выгул (этап 12).
*/

Artisan::command('app:send-walk-reminders', function (WalkBookingService $walks) {
    $this->info('Отправлено напоминаний о прогулках: '.$walks->sendReminders());
})->purpose('Напомнить волонтёрам о подтверждённых прогулках за сутки (walks.reminder_hours)');

Artisan::command('app:expire-unconfirmed-bookings', function (WalkBookingService $walks) {
    $this->info('Отменено неподтверждённых записей: '.$walks->expireUnconfirmed());
})->purpose('Отменить записи на выгул, которые приют не подтвердил до начала прогулки');

/*
| Расписание. В Docker его выполняет контейнер scheduler (php artisan schedule:work),
| без Docker — `php artisan schedule:work` в отдельном окне терминала.
*/

Schedule::command('app:expire-pending-donations')->everyTenMinutes()->withoutOverlapping();
Schedule::command('app:close-expired-campaigns')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('app:send-walk-reminders')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('app:expire-unconfirmed-bookings')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('queue:prune-failed', ['--hours' => 24 * 14])->daily();
