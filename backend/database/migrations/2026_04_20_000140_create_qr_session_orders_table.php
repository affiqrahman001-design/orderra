<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_session_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('qr_session_id')->constrained('qr_sessions')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('linked_from_cart_id')->nullable()->constrained('carts')->nullOnDelete();
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->unique(['qr_session_id', 'order_id']);
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_session_orders');
    }
};
