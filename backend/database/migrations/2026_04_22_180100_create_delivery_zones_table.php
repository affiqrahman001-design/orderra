<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('branch_code', 40);
            $table->string('code', 40);
            $table->string('name', 120);
            $table->string('status', 40)->default('active');
            $table->string('pricing_strategy', 40)->default('hybrid');
            $table->integer('minimum_order_amount')->nullable();
            $table->integer('base_fee_amount')->default(0);
            $table->integer('fee_per_km_amount')->nullable();
            $table->integer('free_delivery_threshold_amount')->nullable();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['branch_code', 'code']);
            $table->index(['branch_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
