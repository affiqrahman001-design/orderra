<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable()->index();

            $table->char('country_code', 2)->default('US');
            $table->string('state_code', 10)->nullable();
            $table->string('city_code', 50)->nullable();
            $table->string('fulfillment_type', 20)->nullable();

            $table->string('name');
            $table->unsignedInteger('rate_bps');

            $table->boolean('applies_to_subtotal')->default(true);
            $table->boolean('applies_to_service_fee')->default(false);
            $table->boolean('applies_to_delivery_fee')->default(false);
            $table->boolean('applies_to_small_order_fee')->default(false);

            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
