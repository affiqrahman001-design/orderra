<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('payment_intent_id')
                ->nullable()
                ->constrained('payment_intents')
                ->nullOnDelete();

            $table->foreignId('payment_transaction_id')
                ->nullable()
                ->constrained('payment_transactions')
                ->nullOnDelete();

            $table->string('category', 50);
            $table->string('status', 30)->default('requested');
            $table->string('resolution_type', 30)->nullable();

            $table->char('currency', 3)->default((string) config('payments.default_currency', 'USD'));

            $table->integer('requested_amount');
            $table->integer('approved_amount')->nullable();
            $table->integer('resolved_amount')->nullable();

            $table->string('reason', 150)->nullable();
            $table->json('policy_snapshot')->nullable();
            $table->json('context_snapshot')->nullable();
            $table->text('notes')->nullable();

            $table->string('requested_by_type', 30)->nullable();
            $table->unsignedBigInteger('requested_by_id')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['category', 'status']);
            $table->index(['payment_intent_id']);
        });

        Schema::create('refund_events', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('refund_id')
                ->constrained('refunds')
                ->cascadeOnDelete();

            $table->string('event_name', 50);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('note')->nullable();
            $table->json('payload')->nullable();

            $table->string('actor_type', 30)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();

            $table->timestamp('occurred_at')->nullable();

            $table->timestamps();

            $table->index(['refund_id', 'event_name']);
            $table->index(['to_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_events');
        Schema::dropIfExists('refunds');
    }
};
