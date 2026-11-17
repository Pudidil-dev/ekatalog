<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->unsignedSmallInteger('sort_order');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('sku', 80)->unique();
            $table->string('slug', 160)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->json('specifications');
            $table->decimal('price', 14, 0)->default(0);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('status', 16)->default('draft');
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestampTz('stock_updated_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['status', 'deleted_at', 'category_id']);
        });
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('object_path', 260)->unique();
            $table->string('alt_text', 180);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestampsTz();
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');
            $table->integer('delta');
            $table->unsignedInteger('request_version');
            $table->string('reason', 500);
            $table->uuid('request_key')->unique();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['product_id', 'created_at']);
        });
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name', 120);
            $table->string('short_description', 280)->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX users_email_ci ON app.users (lower(email))');
            DB::statement('CREATE UNIQUE INDEX products_sku_ci ON app.products (lower(sku))');
            DB::statement("ALTER TABLE app.products ADD CONSTRAINT products_stock_nonnegative CHECK (stock_quantity >= 0), ADD CONSTRAINT products_price_nonnegative CHECK (price >= 0), ADD CONSTRAINT products_status_valid CHECK (status IN ('draft', 'published')), ADD CONSTRAINT products_specs_object CHECK (jsonb_typeof(specifications::jsonb) = 'object'), ADD CONSTRAINT products_publish_price CHECK (status <> 'published' OR price > 0)");
            DB::statement('CREATE UNIQUE INDEX product_images_one_cover ON app.product_images(product_id) WHERE is_cover = true');
            DB::statement('ALTER TABLE app.stock_movements ADD CONSTRAINT movement_delta_valid CHECK (delta = quantity_after::bigint - quantity_before::bigint)');
            DB::statement("CREATE INDEX products_public_category ON app.products(category_id, id) WHERE status = 'published' AND deleted_at IS NULL");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
