<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'pet_id']);
        });

        // Одна активная заявка на (pet, user, type) проверяется в сервисе (без частичных индексов).
        Schema::create('adoption_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);                       // App\Enums\ApplicationType
            $table->string('contact_name', 100);
            $table->string('contact_phone', 16);
            $table->string('city', 100);
            $table->text('experience')->nullable();
            $table->text('living_conditions')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');     // App\Enums\ApplicationStatus
            $table->text('shelter_comment')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['pet_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adoption_applications');
        Schema::dropIfExists('favorites');
    }
};
