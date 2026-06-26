<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);

            $table->string('changed_by_type', 30)->nullable();
            $table->unsignedBigInteger('changed_by_id')->nullable();

            $table->text('reason')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'to_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
