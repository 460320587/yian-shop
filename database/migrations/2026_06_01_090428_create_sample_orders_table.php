<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sample_orders', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete()->comment('客户ID');
            $table->string('order_no', 20)->unique()->comment('样品订单号');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->comment('商品ID');
            $table->unsignedInteger('quantity')->default(1)->comment('数量');
            $table->unsignedBigInteger('unit_price')->default(0)->comment('单价 分');
            $table->unsignedBigInteger('discount_amount')->default(0)->comment('折扣金额 分');
            $table->unsignedBigInteger('total_amount')->default(0)->comment('应付总额 分');
            $table->unsignedTinyInteger('status')->default(100)->comment('状态 100待付款101已付款102待发货103已发货104已完成105已取消');
            $table->json('address_snapshot')->nullable()->comment('收货地址快照');
            $table->string('remark', 500)->nullable()->comment('订单备注');
            $table->timestamp('paid_at')->nullable()->comment('付款时间');
            $table->timestamp('shipped_at')->nullable()->comment('发货时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamp('cancelled_at')->nullable()->comment('取消时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_sample_orders_customer_id');
            $table->index('order_no', 'idx_sample_orders_order_no');
            $table->index('status', 'idx_sample_orders_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_orders');
    }
};
