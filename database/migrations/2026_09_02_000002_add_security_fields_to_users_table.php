<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('password');
            $table->timestamp('password_reset_requested_at')->nullable()->after('password_changed_at');
            $table->string('password_reset_requested_by', 50)->nullable()->after('password_reset_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'password_changed_at',
                'password_reset_requested_at',
                'password_reset_requested_by',
            ]);
        });
    }
};
