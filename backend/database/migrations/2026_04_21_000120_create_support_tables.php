<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

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

            $table->string('category', 50);
            $table->string('status', 30)->default('open');

            $table->string('subject', 150);
            $table->text('description');

            $table->text('resolution_summary')->nullable();
            $table->json('contact_snapshot')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('opened_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['category', 'status']);
            $table->index(['order_id', 'status']);
        });

        Schema::create('support_ticket_events', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('support_ticket_id')
                ->constrained('support_tickets')
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

            $table->index(['support_ticket_id', 'event_name']);
            $table->index(['to_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_events');
        Schema::dropIfExists('support_tickets');
    }
};
