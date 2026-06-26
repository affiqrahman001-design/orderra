<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();

            $table->unsignedBigInteger('menu_item_id')->nullable()->index();
            $table->unsignedBigInteger('variant_id')->nullable()->index();

            $table->string('item_name');
            $table->string('item_slug');
            $table->json('item_snapshot');
            $table->json('modifier_snapshot')->nullable();

            $table->unsignedInteger('quantity')->default(1);

            $table->integer('unit_base_amount');
            $table->integer('unit_modifier_amount')->default(0);
            $table->integer('unit_price_amount');
            $table->integer('line_subtotal_amount');

            $table->text('note')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['cart_id', 'item_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
