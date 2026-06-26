<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable()->index();

            $table->string('code')->unique();
            $table->string('name');
            $table->string('fee_kind', 30);
            $table->string('fulfillment_type', 20)->nullable();

            $table->string('calculation_type', 20); // fixed | bps
            $table->integer('fixed_amount')->nullable();
            $table->unsignedInteger('percentage_bps')->nullable();

            $table->integer('threshold_amount')->nullable();
            $table->integer('min_amount')->nullable();
            $table->integer('max_amount')->nullable();

            $table->boolean('taxable')->default(false);
            $table->json('conditions_json')->nullable();

            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_rules');
    }
};
