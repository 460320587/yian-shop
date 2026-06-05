<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_records', function (Blueprint $table): void {
            $table->id()->comment('退款ID');
            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade')
                ->comment('订单ID');
            $table->foreignId('payment_id')
                ->constrained('payments')
                ->onDelete('cascade')
                ->comment('支付ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('客户ID');
            $table->string('refund_no', 32)->unique()->comment('退款单号');
            $table->unsignedBigInteger('amount')->comment('退款金额 分');
            $table->string('reason', 200)->comment('退款原因');
            $table->unsignedTinyInteger('status')->default(0)
                ->comment('状态 0待审核1已通过2已拒绝3处理中4已完成');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('admins')
                ->onDelete('set null')
                ->comment('审批人');
            $table->timestamp('approved_at')->nullable()->comment('审批时间');
            $table->string('refund_path', 20)->default('original')->comment('退款路径 original/wallet/bank_card');
            $table->string('gateway_refund_no', 64)->nullable()->comment('网关退款流水号');
            $table->timestamp('completed_at')->nullable()->comment('退款完成时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_id', 'idx_order_id');
            $table->index('payment_id', 'idx_payment_id');
            $table->index('customer_id', 'idx_customer_id');
            $table->index('status', 'idx_status');
            $table->index('refund_no', 'idx_refund_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_records');
    }
};
