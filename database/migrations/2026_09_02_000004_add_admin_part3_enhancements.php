<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add admin_role to users
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'admin_role')) {
                $table->string('admin_role', 32)->default('staff')->after('is_admin');
            }
        });

        // Ensure all existing admin accounts are super_admin
        DB::table('users')->where('is_admin', true)->update(['admin_role' => 'super_admin']);

        // 2. Enhance products table with specs, SEO, and low stock threshold
        Schema::table('products', function (Blueprint $table): void {
            if (!Schema::hasColumn('products', 'low_stock_threshold')) {
                $table->integer('low_stock_threshold')->nullable()->after('inventory');
            }
            if (!Schema::hasColumn('products', 'material')) {
                $table->string('material', 255)->nullable()->after('description');
            }
            if (!Schema::hasColumn('products', 'weight')) {
                $table->string('weight', 100)->nullable()->after('material');
            }
            if (!Schema::hasColumn('products', 'dimensions')) {
                $table->string('dimensions', 100)->nullable()->after('weight');
            }
            if (!Schema::hasColumn('products', 'seo_title')) {
                $table->string('seo_title', 255)->nullable()->after('dimensions');
            }
            if (!Schema::hasColumn('products', 'seo_description')) {
                $table->text('seo_description')->nullable()->after('seo_title');
            }
        });

        // 3. Enhance product_variants table
        Schema::table('product_variants', function (Blueprint $table): void {
            if (!Schema::hasColumn('product_variants', 'attribute_name')) {
                $table->string('attribute_name', 100)->default('Color')->after('title');
            }
            if (!Schema::hasColumn('product_variants', 'attribute_value')) {
                $table->string('attribute_value', 100)->nullable()->after('attribute_name');
            }
            if (!Schema::hasColumn('product_variants', 'price_override_minor')) {
                $table->unsignedBigInteger('price_override_minor')->nullable()->after('retail_price_minor');
            }
            if (!Schema::hasColumn('product_variants', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('attributes_json');
            }
        });

        // 4. Create shipping_zones table
        if (!Schema::hasTable('shipping_zones')) {
            Schema::create('shipping_zones', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 150);
                $table->json('governorates_json')->nullable();
                $table->unsignedBigInteger('rate_minor')->default(7500); // e.g. 75 EGP
                $table->string('estimated_days', 100)->default('1-2 Business Days');
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });

            // Seed default Egyptian zones
            DB::table('shipping_zones')->insert([
                [
                    'name'              => 'Greater Cairo (Cairo, Giza, Helwan, 6th of October)',
                    'governorates_json' => json_encode(['Cairo', 'Giza', 'Helwan', '6th of October', 'New Cairo']),
                    'rate_minor'        => 6000, // 60 EGP
                    'estimated_days'    => '24-48 Hours Express',
                    'is_active'         => true,
                    'sort_order'        => 1,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ],
                [
                    'name'              => 'Alexandria & Coastal Governorates',
                    'governorates_json' => json_encode(['Alexandria', 'Beheira', 'Matrouh', 'Port Said', 'Damietta']),
                    'rate_minor'        => 7500, // 75 EGP
                    'estimated_days'    => '2-3 Business Days',
                    'is_active'         => true,
                    'sort_order'        => 2,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ],
                [
                    'name'              => 'Delta & Canal Governorates',
                    'governorates_json' => json_encode(['Dakahlia', 'Gharbia', 'Monufia', 'Sharqia', 'Qalyubia', 'Ismailia', 'Suez']),
                    'rate_minor'        => 8000, // 80 EGP
                    'estimated_days'    => '2-3 Business Days',
                    'is_active'         => true,
                    'sort_order'        => 3,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ],
                [
                    'name'              => 'Upper Egypt & Red Sea',
                    'governorates_json' => json_encode(['Fayoum', 'Beni Suef', 'Minya', 'Assiut', 'Sohag', 'Qena', 'Luxor', 'Aswan', 'Red Sea', 'South Sinai', 'North Sinai']),
                    'rate_minor'        => 9500, // 95 EGP
                    'estimated_days'    => '3-4 Business Days',
                    'is_active'         => true,
                    'sort_order'        => 4,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn(['attribute_name', 'attribute_value', 'price_override_minor', 'image_url']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['low_stock_threshold', 'material', 'weight', 'dimensions', 'seo_title', 'seo_description']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('admin_role');
        });
    }
};
