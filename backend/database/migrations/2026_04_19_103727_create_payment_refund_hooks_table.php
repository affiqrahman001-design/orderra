<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_refund_hooks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('payment_intent_id')
                ->constrained('payment_intents')
                ->cascadeOnDelete();

            $table->foreignId('payment_transaction_id')
                ->nullable()
                ->constrained('payment_transactions')
                ->nullOnDelete();

            $table->string('hook_type', 50);
            $table->string('status', 30)->default('recorded');

            $table->integer('amount')->nullable();
            $table->char('currency', 3)->default((string) config('payments.default_currency', 'USD'));

            $table->string('reason', 120)->nullable();
            $table->json('payload')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['payment_intent_id', 'hook_type']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refund_hooks');
    }
};
