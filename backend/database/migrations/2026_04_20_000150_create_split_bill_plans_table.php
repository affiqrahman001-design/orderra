<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('split_bill_plans', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('qr_session_id')->constrained('qr_sessions')->cascadeOnDelete();
            $table->string('split_type', 20);
            $table->string('status', 20)->default('draft');
            $table->string('currency', 3)->default('USD');
            $table->json('session_totals_snapshot');
            $table->json('rules_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['qr_session_id', 'status']);
            $table->index(['split_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_bill_plans');
    }
};
