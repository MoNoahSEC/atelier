<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Page views tracking table for Analytics
        if (!Schema::hasTable('page_views')) {
            Schema::create('page_views', function (Blueprint $table) {
                $table->id();
                $table->string('page_type', 50)->default('page'); // homepage, product, collection, custom
                $table->unsignedBigInteger('page_id')->nullable(); // product_id or collection_id
                $table->string('visitor_id', 100)->nullable()->index();
                $table->string('url')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        // 2. Add last_login_at to users table if missing
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_login_at')->nullable()->after('password_changed_at');
            });
        }

        // 3. Add compare_at_price_minor to products table if missing
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'compare_at_price_minor')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('compare_at_price_minor')->nullable()->after('retail_price_minor');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
        if (Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('last_login_at');
            });
        }
        if (Schema::hasColumn('products', 'compare_at_price_minor')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('compare_at_price_minor');
            });
        }
    }
};
