<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('购物车项ID');
            $table->foreignId('cart_id')
                ->constrained('carts')
                ->onDelete('cascade')
                ->comment('购物车ID');
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('restrict')
                ->comment('商品ID');
            $table->unsignedInteger('quantity')->default(1)->comment('数量');
            $table->unsignedBigInteger('unit_price')->default(0)->comment('单价 分');
            $table->unsignedBigInteger('subtotal')->default(0)->comment('小计 分');
            $table->unsignedTinyInteger('selected')->default(1)->comment('选中 1:是 0:否');
            $table->json('spec_info')->nullable()->comment('规格参数快照');
            $table->timestamps();
            $table->softDeletes();

            $table->index('cart_id', 'idx_cart_id');
            $table->index('product_id', 'idx_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
