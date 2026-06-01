<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('支付单ID');
            $table->string('payment_no', 20)->unique()->comment('支付单号');
            $table->string('order_no', 20)->nullable()->comment('关联订单号');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('restrict')
                ->comment('客户ID');
            $table->string('gateway', 20)->comment('支付渠道');
            $table->unsignedBigInteger('amount')->default(0)->comment('支付金额 分');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1待支付2成功3失败4关闭');
            $table->string('transaction_no', 64)->nullable()->comment('第三方流水号');
            $table->json('credential')->nullable()->comment('支付凭证');
            $table->timestamp('paid_at')->nullable()->comment('支付成功时间');
            $table->timestamp('expire_at')->nullable()->comment('过期时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_no', 'idx_order_no');
            $table->index('customer_id', 'idx_customer_id');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
