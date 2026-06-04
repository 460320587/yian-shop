<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('thumbnail', 500)->nullable()->after('cover_image')->comment('缩略图');
            $table->unsignedInteger('sales_count')->default(0)->after('thumbnail')->comment('销量');
            $table->unsignedTinyInteger('is_hot')->default(0)->after('sales_count')->comment('是否热销 0:否 1:是');
            $table->unsignedTinyInteger('is_new')->default(0)->after('is_hot')->comment('是否新品 0:否 1:是');

            $table->index('is_hot', 'idx_products_is_hot');
            $table->index('is_new', 'idx_products_is_new');
            $table->index('sales_count', 'idx_products_sales_count');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('idx_products_is_hot');
            $table->dropIndex('idx_products_is_new');
            $table->dropIndex('idx_products_sales_count');
            $table->dropColumn(['thumbnail', 'sales_count', 'is_hot', 'is_new']);
        });
    }
};
