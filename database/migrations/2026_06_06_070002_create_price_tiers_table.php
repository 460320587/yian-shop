<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_tiers', function (Blueprint $table): void {
            $table->id()->comment('价格阶梯主键');
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->integer('min_qty')->default(1)->comment('最小数量');
            $table->integer('max_qty')->default(0)->comment('最大数量(0=不限)');
            $table->decimal('unit_price', 12, 4)->default(0)->comment('单价');
            $table->tinyInteger('status')->default(1)->comment('1=启用 0=禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id', 'idx_product_id');
            $table->index(['min_qty', 'max_qty'], 'idx_qty_range');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_tiers');
    }
};
