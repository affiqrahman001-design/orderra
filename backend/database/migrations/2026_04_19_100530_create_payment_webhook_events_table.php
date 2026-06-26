<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('payment_intent_id')
                ->nullable()
                ->constrained('payment_intents')
                ->nullOnDelete();

            $table->string('provider_code', 50);
            $table->string('event_name', 50);
            $table->string('delivery_status', 30)->default('processed');

            $table->string('provider_reference', 100)->nullable();

            $table->json('headers')->nullable();
            $table->json('payload')->nullable();

            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['payment_intent_id', 'event_name']);
            $table->index(['provider_code', 'event_name']);
            $table->index(['delivery_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
