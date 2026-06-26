<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ops_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->string('event_name', 80);
            $table->string('aggregate_type', 40);
            $table->string('status', 30)->default('processed');

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->foreignId('refund_id')
                ->nullable()
                ->constrained('refunds')
                ->nullOnDelete();

            $table->foreignId('payment_intent_id')
                ->nullable()
                ->constrained('payment_intents')
                ->nullOnDelete();

            $table->foreignId('delivery_assignment_id')
                ->nullable()
                ->constrained('delivery_assignments')
                ->nullOnDelete();

            $table->json('payload')->nullable();
            $table->json('headers')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->timestamp('last_replayed_at')->nullable();
            $table->unsignedInteger('replay_count')->default(0);

            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['event_name', 'status']);
            $table->index(['aggregate_type', 'status']);
            $table->index(['order_id']);
            $table->index(['refund_id']);
            $table->index(['payment_intent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ops_webhook_events');
    }
};
