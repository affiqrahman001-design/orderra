<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('split_bill_participants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('split_bill_plan_id')->constrained('split_bill_plans')->cascadeOnDelete();
            $table->string('display_name', 80);
            $table->string('seat_label', 40)->nullable();
            $table->unsignedInteger('participant_order')->default(1);
            $table->boolean('is_primary_payer')->default(false);
            $table->string('status', 20)->default('active');

            $table->integer('subtotal_amount')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('service_fee_amount')->default(0);
            $table->integer('delivery_fee_amount')->default(0);
            $table->integer('small_order_fee_amount')->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('tip_amount')->default(0);
            $table->integer('total_amount')->default(0);

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['split_bill_plan_id', 'participant_order']);
            $table->index(['split_bill_plan_id', 'is_primary_payer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_bill_participants');
    }
};
