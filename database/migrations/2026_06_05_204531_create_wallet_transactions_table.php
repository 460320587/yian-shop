<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->id()->comment('流水ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('restrict')
                ->comment('客户ID');
            $table->unsignedTinyInteger('type')
                ->comment('类型 1充值2消费3退款4提现5冻结6解冻');
            $table->bigInteger('amount')->comment('变动金额 分（正增负减）');
            $table->unsignedBigInteger('balance_before')->comment('变动前余额 分');
            $table->unsignedBigInteger('balance_after')->comment('变动后余额 分');
            $table->string('order_no', 32)->nullable()->comment('关联订单号');
            $table->string('payment_no', 32)->nullable()->comment('关联支付单号');
            $table->string('remark', 200)->nullable()->comment('备注');
            $table->unsignedTinyInteger('status')->default(1)
                ->comment('状态 1成功2处理中3失败');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_customer_id');
            $table->index(['customer_id', 'type'], 'idx_customer_type');
            $table->index(['customer_id', 'status'], 'idx_customer_status');
            $table->index('order_no', 'idx_order_no');
            $table->index('created_at', 'idx_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
