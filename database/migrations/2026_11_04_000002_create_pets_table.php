<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelter_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('slug')->unique();
            $table->string('species', 10)->index(); // App\Enums\Species
            $table->string('sex', 10);              // App\Enums\Sex
            $table->date('birth_date')->nullable();
            $table->boolean('birth_date_is_approx')->default(false);
            $table->string('size', 10);             // App\Enums\PetSize
            $table->string('breed', 100)->nullable();
            $table->string('color', 100)->nullable();
            $table->text('character');
            // Черты характера — отдельные bool-колонки (переносимо, без JSON/массивов)
            $table->boolean('good_with_kids')->default(false);
            $table->boolean('good_with_cats')->default(false);
            $table->boolean('good_with_dogs')->default(false);
            $table->boolean('house_trained')->default(false);
            $table->boolean('leash_trained')->default(false);
            // Здоровье
            $table->boolean('vaccinated')->default(false);
            $table->boolean('sterilized')->default(false);
            $table->boolean('chipped')->default(false);
            $table->text('health_notes')->nullable();
            $table->text('story')->nullable();
            $table->string('status', 20)->default('draft'); // App\Enums\PetStatus
            $table->boolean('can_walk')->default(false);
            $table->boolean('guardianship_available')->default(false);
            $table->date('arrived_at')->nullable();
            $table->timestamp('adopted_at')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->index(['status', 'is_hidden']);
            $table->index(['shelter_id', 'status']);
            $table->index('arrived_at');
        });

        Schema::create('pet_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();

            $table->index(['pet_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_photos');
        Schema::dropIfExists('pets');
    }
};
