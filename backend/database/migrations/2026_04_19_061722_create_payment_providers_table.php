<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_providers', function (Blueprint $table): void {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('label', 100);
            $table->string('driver', 30);
            $table->string('mode', 20)->default('demo');

            $table->boolean('is_active')->default(true);
            $table->boolean('live_enabled')->default(false);
            $table->boolean('webhook_enabled')->default(false);
            $table->boolean('supports_refunds')->default(false);

            $table->json('settings')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['driver', 'mode']);
            $table->index(['is_active', 'live_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_providers');
    }
};
