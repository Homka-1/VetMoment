<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Этап 13: индексы под выборки по датам пожертвований.
 * (status, paid_at) — статистика админки за месяц, «собрано за месяц» приюта, сумма за год у волонтёра,
 * лента пожертвований сбора; (status, created_at) — команда истечения неоплаченных платежей (каждые 10 минут).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->index(['status', 'paid_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropIndex(['status', 'paid_at']);
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
