<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Шаблон слотов на неделю
        Schema::create('walk_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelter_id')->constrained()->cascadeOnDelete();
            // Дни недели битовой маской: Пн = 1, Вт = 2, … Вс = 64 (App\Models\WalkTemplate)
            $table->unsignedTinyInteger('weekdays');
            $table->time('start_time');               // местное время Asia/Almaty
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('capacity');
            $table->string('meeting_point')->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });

        Schema::create('walk_template_pet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('walk_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();

            $table->unique(['walk_template_id', 'pet_id']);
        });

        Schema::create('walk_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('walk_templates')->nullOnDelete();
            $table->timestamp('starts_at'); // UTC
            $table->timestamp('ends_at');
            $table->unsignedSmallInteger('capacity');
            $table->unsignedSmallInteger('booked_count')->default(0); // активные записи (pending + confirmed)
            $table->string('meeting_point')->nullable();
            $table->string('status', 20)->default('open'); // App\Enums\WalkSlotStatus
            $table->timestamps();

            $table->index(['shelter_id', 'starts_at']);
            $table->index(['status', 'starts_at']);
        });

        // Питомцы, доступные в слоте; пусто → «питомца назначит приют»
        Schema::create('walk_slot_pet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('walk_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();

            $table->unique(['walk_slot_id', 'pet_id']);
        });

        Schema::create('walk_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('walk_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending'); // App\Enums\BookingStatus
            $table->string('comment', 500)->nullable();
            $table->string('shelter_comment', 500)->nullable();
            $table->string('cancelled_by', 10)->nullable();   // App\Enums\CancelledBy
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['walk_slot_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['pet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('walk_bookings');
        Schema::dropIfExists('walk_slot_pet');
        Schema::dropIfExists('walk_slots');
        Schema::dropIfExists('walk_template_pet');
        Schema::dropIfExists('walk_templates');
    }
};
