<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('restaurant_table_id')->constrained('restaurant_tables')->cascadeOnDelete();
            $table->string('session_code', 40)->unique();
            $table->string('status', 30)->default((string) config('dine_in.qr_sessions.default_status', 'open'));
            $table->unsignedSmallInteger('party_size')->default((int) config('dine_in.qr_sessions.default_party_size', 1));
            $table->foreignId('opened_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('opened_via', 30)->default('qr');
            $table->json('meta')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('bill_requested_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_table_id', 'status']);
            $table->index(['status', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_sessions');
    }
};
