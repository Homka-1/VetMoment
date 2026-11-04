<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // публичный идентификатор: /donations/{uuid}
            // null → в общий фонд приюта; удалить сбор с пожертвованиями нельзя (история)
            $table->foreignId('campaign_id')->nullable()->constrained('donation_campaigns')->restrictOnDelete();
            $table->foreignId('shelter_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('donor_name', 100)->nullable();
            $table->string('donor_email');
            $table->boolean('is_anonymous')->default(false);
            $table->unsignedBigInteger('amount'); // тиын
            $table->string('comment', 500)->nullable();
            $table->string('status', 20)->default('pending'); // App\Enums\PaymentStatus
            $table->string('provider', 30)->default('fake');
            $table->string('provider_payment_id')->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Идемпотентность webhook: один платёж провайдера — одно пожертвование.
            // NULL в уникальном индексе не конфликтует ни в PostgreSQL, ни в SQLite.
            $table->unique(['provider', 'provider_payment_id']);
            $table->index(['campaign_id', 'status']);
            $table->index(['shelter_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
