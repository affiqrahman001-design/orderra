<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->string('channel', 40);
            $table->string('notification_type', 120);
            $table->string('status', 40)->default('simulated');

            $table->string('provider_code', 80)->nullable();

            $table->string('recipient_type', 40)->nullable();
            $table->string('recipient_key', 160)->nullable();

            $table->string('entity_type', 80)->nullable();
            $table->string('entity_public_id', 80)->nullable();

            $table->string('subject', 180)->nullable();
            $table->string('title', 180)->nullable();
            $table->text('body_preview')->nullable();

            $table->jsonb('meta')->nullable();
            $table->text('error_message')->nullable();

            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('failed_at')->nullable();

            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index(['notification_type', 'status']);
            $table->index(['entity_type', 'entity_public_id']);
            $table->index(['recipient_type', 'recipient_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
