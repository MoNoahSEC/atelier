<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

/**
 * SQLite does not support ALTER COLUMN to change enum/check constraints,
 * so this migration is a no-op at the schema level.
 *
 * The payment `status` column is a VARCHAR(32) with no check constraint,
 * so adding 'reconciling' as a valid status requires no DDL change.
 * The application code enforces valid state values.
 *
 * This migration is intentionally kept as documentation only.
 */
return new class extends Migration
{
    public function up(): void
    {
        // No DDL change needed: status is VARCHAR(32), 'reconciling' is a valid string.
        // Application-layer state machine controls valid transitions.
    }

    public function down(): void
    {
        // No DDL to reverse.
    }
};
