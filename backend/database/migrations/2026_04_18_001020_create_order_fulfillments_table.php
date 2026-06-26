<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_fulfillments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();

            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('fulfillment_type', 20);

            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 50)->nullable();

            $table->timestamp('scheduled_for')->nullable();
            $table->unsignedInteger('eta_minutes')->nullable();

            $table->string('pickup_code', 20)->nullable();

            $table->string('table_label', 50)->nullable();
            $table->unsignedInteger('party_size')->nullable();

            $table->json('address_snapshot')->nullable();
            $table->json('context_snapshot')->nullable();

            $table->timestamps();

            $table->index(['fulfillment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_fulfillments');
    }
};
