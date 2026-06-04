<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('after_sale_items', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->foreignId('after_sale_id')->constrained('after_sales')->cascadeOnDelete()->comment('售后单ID');
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete()->comment('订单项ID');
            $table->string('product_name', 200)->comment('商品名快照');
            $table->unsignedInteger('quantity')->default(1)->comment('售后数量');
            $table->unsignedBigInteger('unit_refund')->default(0)->comment('单项退款金额 分');
            $table->timestamps();
            $table->softDeletes();

            $table->index('after_sale_id', 'idx_after_sale_items_after_sale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('after_sale_items');
    }
};
