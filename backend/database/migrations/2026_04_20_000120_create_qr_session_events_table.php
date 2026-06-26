<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_session_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('qr_session_id')->constrained('qr_sessions')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('cart_id')->nullable()->constrained('carts')->nullOnDelete();
            $table->string('event_type', 40);
            $table->string('actor_type', 30)->default('customer');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['qr_session_id', 'event_type']);
            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_session_events');
    }
};
