<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('split_bill_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('split_bill_plan_id')->constrained('split_bill_plans')->cascadeOnDelete();
            $table->foreignId('split_bill_participant_id')->constrained('split_bill_participants')->cascadeOnDelete();

            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();

            $table->string('allocation_type', 20);
            $table->unsignedInteger('quantity')->nullable();
            $table->string('item_name', 150)->nullable();
            $table->string('item_slug', 150)->nullable();
            $table->integer('subtotal_amount')->default(0);

            $table->json('source_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['split_bill_plan_id', 'allocation_type']);
            $table->index(['split_bill_participant_id']);
            $table->index(['order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_bill_allocations');
    }
};
