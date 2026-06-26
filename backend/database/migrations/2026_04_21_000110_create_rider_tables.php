<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('rider_code', 50)->unique();
            $table->string('name', 120);
            $table->string('phone', 30)->nullable();
            $table->string('type', 40);
            $table->string('status', 30)->default('active');
            $table->string('vehicle_type', 50)->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_demo')->default(true);
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('delivery_assignments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('rider_id')
                ->nullable()
                ->constrained('riders')
                ->nullOnDelete();

            $table->string('provider_type', 40)->default('self');
            $table->string('status', 30)->default('awaiting_rider');
            $table->unsignedInteger('eta_minutes')->default(0);

            $table->json('context_snapshot')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('near_customer_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            $table->unique(['order_id']);
            $table->index(['status']);
            $table->index(['rider_id', 'status']);
        });

        Schema::create('delivery_tracking_events', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('delivery_assignment_id')
                ->constrained('delivery_assignments')
                ->cascadeOnDelete();

            $table->string('status', 30);
            $table->unsignedInteger('eta_minutes')->default(0);

            $table->decimal('simulated_latitude', 10, 7)->nullable();
            $table->decimal('simulated_longitude', 10, 7)->nullable();

            $table->text('note')->nullable();
            $table->json('payload')->nullable();

            $table->timestamp('occurred_at')->nullable();

            $table->timestamps();

            $table->index(['delivery_assignment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_tracking_events');
        Schema::dropIfExists('delivery_assignments');
        Schema::dropIfExists('riders');
    }
};
