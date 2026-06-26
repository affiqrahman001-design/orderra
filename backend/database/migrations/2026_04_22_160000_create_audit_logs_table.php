<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->string('channel', 40)->default('admin');
            $table->string('action', 120);
            $table->string('status', 40)->default('completed');

            $table->string('actor_type', 40)->nullable();
            $table->string('actor_id', 80)->nullable();

            $table->string('entity_type', 80)->nullable();
            $table->string('entity_public_id', 80)->nullable();
            $table->string('entity_secondary_key', 120)->nullable();

            $table->string('summary', 255)->nullable();

            $table->string('request_method', 20)->nullable();
            $table->string('request_path', 255)->nullable();

            $table->jsonb('request_snapshot')->nullable();
            $table->jsonb('context_snapshot')->nullable();

            $table->timestampTz('occurred_at');
            $table->timestamps();

            $table->index(['channel', 'occurred_at']);
            $table->index(['action', 'status']);
            $table->index(['entity_type', 'entity_public_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
