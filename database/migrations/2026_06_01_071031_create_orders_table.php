<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('订单ID');
            $table->string('order_no', 20)->unique()->comment('订单号');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('restrict')
                ->comment('客户ID');
            $table->unsignedTinyInteger('status')->default(10)->comment('FM状态 10待付款...');
            $table->string('out_status_name', 20)->default('待付款')->comment('nM客户状态名称');
            $table->unsignedBigInteger('total_amount')->default(0)->comment('订单总金额 分');
            $table->unsignedBigInteger('deposit_sum')->default(0)->comment('定金金额 分');
            $table->unsignedBigInteger('discount_sum')->default(0)->comment('优惠金额 分');
            $table->string('express_company', 50)->nullable()->comment('快递公司');
            $table->unsignedTinyInteger('delivery_type')->default(1)->comment('配送类型 1快递2自提3送货上门');
            $table->unsignedTinyInteger('source')->default(1)->comment('来源 1PC2H3小程序4APP');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->timestamp('submitted_at')->nullable()->comment('提交时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_no', 'uk_order_no');
            $table->index('customer_id', 'idx_customer_id');
            $table->index('status', 'idx_status');
            $table->index('created_at', 'idx_created_at');
            $table->index(['customer_id', 'status'], 'idx_customer_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
