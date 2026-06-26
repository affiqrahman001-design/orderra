<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('label', 100);
            $table->string('family', 30);
            $table->string('kind', 20);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_demo_enabled')->default(true);
            $table->boolean('requires_intent')->default(true);
            $table->boolean('supports_manual_simulation')->default(true);

            $table->unsignedInteger('sort_order')->default(100);
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['family', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
