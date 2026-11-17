<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Этап 12: отметка, что напоминание за сутки уже отправлено (команда app:send-walk-reminders).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('walk_bookings', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('walk_bookings', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
