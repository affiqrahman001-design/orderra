<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('order_code', 40)->unique();

            $table->foreignId('cart_id')->nullable()->unique()->constrained('carts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 40);
            $table->char('currency', 3);
            $table->string('fulfillment_type', 20);
            $table->string('source', 30)->default('web');

            $table->json('customer_context_snapshot');
            $table->json('fulfillment_context_snapshot');
            $table->json('pricing_snapshot');

            $table->integer('subtotal_amount')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('service_fee_amount')->default(0);
            $table->integer('delivery_fee_amount')->default(0);
            $table->integer('small_order_fee_amount')->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('tip_amount')->default(0);
            $table->integer('total_amount')->default(0);

            $table->json('meta')->nullable();

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'fulfillment_type']);
            $table->index(['placed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
