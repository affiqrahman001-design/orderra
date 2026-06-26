<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->string('status', 40)->default('active');
            $table->string('country_code', 2);
            $table->string('currency', 3);
            $table->string('timezone', 80);
            $table->string('phone', 40)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('address_line_1', 160)->nullable();
            $table->string('address_line_2', 160)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('postal_code', 40)->nullable();
            $table->boolean('supports_delivery')->default(true);
            $table->boolean('supports_pickup')->default(true);
            $table->boolean('supports_dine_in')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
