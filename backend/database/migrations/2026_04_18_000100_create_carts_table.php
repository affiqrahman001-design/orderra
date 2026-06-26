<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('cart_token', 100)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 40)->default(config('cart.default_status'));
            $table->char('currency', 3)->default(config('cart.defaults.currency'));
            $table->string('fulfillment_type', 20)->default(config('cart.defaults.fulfillment_type'));
            $table->string('source', 30)->default(config('cart.defaults.source'));

            $table->string('promo_code', 50)->nullable();
            $table->json('promo_payload')->nullable();

            $table->string('tip_type', 20)->default(config('cart.defaults.tip_type'));
            $table->integer('tip_value')->default(config('cart.defaults.tip_value'));

            $table->json('customer_context')->nullable();
            $table->json('fulfillment_context')->nullable();
            $table->json('pricing_snapshot')->nullable();

            $table->timestamp('last_priced_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'fulfillment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
