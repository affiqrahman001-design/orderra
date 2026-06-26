<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('payment_intent_id')->constrained('payment_intents')->cascadeOnDelete();

            $table->unsignedInteger('attempt_number')->default(1);

            $table->string('method_code', 50);
            $table->string('provider_code', 50);
            $table->string('status', 30)->default('initiated');

            $table->integer('amount')->default(0);

            $table->string('simulation_outcome', 20)->nullable();
            $table->string('provider_reference', 100)->nullable();

            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('meta')->nullable();

            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->unique(['payment_intent_id', 'attempt_number']);
            $table->index(['payment_intent_id', 'status']);
            $table->index(['status', 'provider_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
