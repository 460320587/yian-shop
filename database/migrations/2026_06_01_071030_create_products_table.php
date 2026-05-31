<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('商品ID');
            $table->foreignId('category_id')
                ->constrained('product_categories')
                ->onDelete('restrict')
                ->comment('分类ID');
            $table->string('name', 100)->comment('商品名称');
            $table->string('code', 50)->nullable()->unique()->comment('商品编码');
            $table->unsignedBigInteger('price_min')->default(0)->comment('最低价格 分');
            $table->unsignedBigInteger('price_max')->default(0)->comment('最高价格 分');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态 0草稿1上架2下架');
            $table->unsignedSmallInteger('sort')->default(0)->comment('排序');
            $table->string('cover_image', 500)->nullable()->comment('封面图');
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id', 'idx_category_id');
            $table->index('status', 'idx_status');
            $table->index('sort', 'idx_sort');
            $table->index('code', 'idx_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
