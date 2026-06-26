<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_intents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('intent_code', 50)->unique();

            $table->foreignId('cart_id')->nullable()->constrained('carts')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('method_code', 50);
            $table->string('provider_code', 50);
            $table->string('status', 30)->default('draft');

            $table->char('country_code', 2)->default((string) config('payments.default_country', 'US'));
            $table->char('currency', 3)->default((string) config('payments.default_currency', 'USD'));

            $table->integer('amount')->default(0);

            $table->string('branch_code', 50)->nullable();

            $table->json('simulation_context')->nullable();
            $table->json('provider_context')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'method_code']);
            $table->index(['status', 'provider_code']);
            $table->index(['cart_id', 'status']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
