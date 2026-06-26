<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('menu_category_id')->constrained('menu_categories')->cascadeOnDelete();
            $table->string('code', 60)->unique();
            $table->string('name', 140);
            $table->string('short_name', 80)->nullable();
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->integer('base_price_amount');
            $table->string('currency', 3)->default('USD');
            $table->string('image_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('badge_label', 60)->nullable();
            $table->string('prep_note', 160)->nullable();
            $table->string('product_flow', 20)->default('full');
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['menu_category_id', 'is_active']);
            $table->index(['branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
