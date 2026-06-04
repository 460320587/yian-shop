<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade')
                ->comment('订单ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('restrict')
                ->comment('客户ID');
            $table->string('invoice_no', 32)->nullable()->unique()->comment('发票号');
            $table->unsignedTinyInteger('type')->default(1)->comment('类型 1:普票 2:专票');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0:已取消 1:已申请 2:已审核 3:已驳回 4:已开票 5:已打印快递单 6:已作废');
            $table->unsignedTinyInteger('business_type')->default(1)->comment('业务类型 0:未指定 1:正常发票 2:负数发票 3:全部红冲');
            $table->string('title', 200)->comment('发票抬头');
            $table->string('tax_number', 20)->nullable()->comment('税号');
            $table->unsignedBigInteger('amount')->comment('发票金额 分');
            $table->string('email', 100)->nullable()->comment('接收邮箱');
            $table->string('address', 255)->nullable()->comment('邮寄地址');
            $table->string('bank_name', 100)->nullable()->comment('开户行');
            $table->string('bank_account', 30)->nullable()->comment('银行账号');
            $table->string('express_no', 50)->nullable()->comment('邮寄运单号');
            $table->timestamp('issued_at')->nullable()->comment('开具时间');
            $table->text('remark')->nullable()->comment('备注/驳回原因');
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_id', 'idx_invoices_order_id');
            $table->index('customer_id', 'idx_invoices_customer_id');
            $table->index('status', 'idx_invoices_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
