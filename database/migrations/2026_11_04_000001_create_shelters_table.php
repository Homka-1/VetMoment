<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelters', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('city', 100)->index();
            $table->string('address');
            $table->string('phone', 16); // E.164 +77XXXXXXXXX
            $table->string('email');
            $table->string('website')->nullable();
            $table->string('bin_iin', 12)->nullable(); // БИН юрлица или ИИН куратора
            $table->string('logo_path')->nullable();
            $table->text('walk_rules')->nullable();
            $table->string('status', 20)->default('pending')->index(); // App\Enums\ShelterStatus
            $table->text('moderation_comment')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // Сотрудники приюта. В MVP — не более одного приюта на пользователя (user_id уникален).
        Schema::create('shelter_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('staff'); // App\Enums\ShelterMemberRole
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelter_user');
        Schema::dropIfExists('shelters');
    }
};
