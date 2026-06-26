<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('payment_intent_id')->constrained('payment_intents')->cascadeOnDelete();
            $table->foreignId('payment_attempt_id')->nullable()->constrained('payment_attempts')->nullOnDelete();

            $table->string('transaction_type', 30);
            $table->string('direction', 20)->default('debit');
            $table->string('status', 30)->default('pending');

            $table->string('method_code', 50);
            $table->string('provider_code', 50);

            $table->char('currency', 3)->default((string) config('payments.default_currency', 'USD'));
            $table->integer('amount')->default(0);

            $table->string('provider_reference', 100)->nullable();
            $table->string('external_reference', 100)->nullable();

            $table->json('payload')->nullable();

            $table->timestamp('occurred_at')->nullable();

            $table->timestamps();

            $table->index(['payment_intent_id', 'transaction_type']);
            $table->index(['status', 'provider_code']);
            $table->index(['occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
