<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donation_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 120);
            $table->string('slug')->unique();
            $table->string('short_description', 200);
            $table->text('description');
            // Деньги — в тиынах (1 ₸ = 100 тиын)
            $table->unsignedBigInteger('goal_amount');
            $table->unsignedBigInteger('raised_amount')->default(0);   // денормализация
            $table->unsignedInteger('donations_count')->default(0);    // денормализация
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->boolean('allow_overfunding')->default(false);
            $table->string('cover_path')->nullable();
            $table->string('status', 20)->default('draft');            // App\Enums\CampaignStatus
            $table->boolean('is_hidden')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_hidden']);
            $table->index(['shelter_id', 'status']);
        });

        Schema::create('campaign_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('donation_campaigns')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('body');
            $table->unsignedBigInteger('spent_amount')->nullable(); // тиын
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'published_at']);
        });

        Schema::create('report_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_report_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('type', 10)->default('photo'); // App\Enums\AttachmentType
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_attachments');
        Schema::dropIfExists('campaign_reports');
        Schema::dropIfExists('donation_campaigns');
    }
};
