<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        /*
         | Legacy compatibility migration.
         |
         | The official tax_rules schema is created by:
         | 2026_04_18_000120_create_tax_rules_table.php
         |
         | This later duplicate migration is intentionally kept as a no-op so
         | existing migration history stays stable without creating a second,
         | conflicting tax_rules schema.
         */
    }

    public function down(): void
    {
        /*
         | No-op on rollback.
         |
         | The official tax_rules table is dropped by the original migration.
         | Do not drop it here, otherwise rollback order can remove the real table
         | before the official migration runs.
         */
    }
};
