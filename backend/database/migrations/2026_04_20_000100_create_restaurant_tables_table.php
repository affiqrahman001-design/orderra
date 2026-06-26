<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('code', 40)->unique();
            $table->string('label', 100);
            $table->unsignedSmallInteger('seat_capacity')->default(2);
            $table->string('status', 20)->default((string) config('dine_in.tables.default_status', 'active'));
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'seat_capacity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};
