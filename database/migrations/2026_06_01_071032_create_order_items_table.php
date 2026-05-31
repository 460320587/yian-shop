<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('订单明细ID');
            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade')
                ->comment('订单ID');
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('restrict')
                ->comment('商品ID');
            $table->string('product_name', 100)->comment('商品名称快照');
            $table->string('spec_info', 500)->nullable()->comment('规格信息');
            $table->unsignedInteger('quantity')->default(1)->comment('数量');
            $table->unsignedBigInteger('unit_price')->default(0)->comment('单价 分');
            $table->unsignedBigInteger('total_price')->default(0)->comment('小计 分');
            $table->string('file_url', 500)->nullable()->comment('印前文件URL');
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_id', 'idx_order_id');
            $table->index('product_id', 'idx_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
