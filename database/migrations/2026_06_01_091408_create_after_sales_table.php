<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('after_sales', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->string('after_sale_no', 20)->unique()->comment('售后单号');
            $table->string('order_no', 20)->comment('关联订单号');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete()->comment('客户ID');
            $table->unsignedTinyInteger('type')->default(1)->comment('类型 1:退款 2:退货退款 3:换货 4:补发 5:维修');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1:待审核 2:审核通过 3:审核拒绝 4:处理中 5:已完成 6:已关闭');
            $table->string('reason', 500)->comment('申请原因');
            $table->text('description')->nullable()->comment('详细描述');
            $table->json('images')->nullable()->comment('凭证图片URL数组');
            $table->unsignedBigInteger('refund_amount')->default(0)->comment('申请退款金额 分');
            $table->unsignedBigInteger('approved_amount')->default(0)->comment('审核通过金额 分');
            $table->string('audit_remark', 500)->nullable()->comment('审核备注');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_after_sales_customer_id');
            $table->index('order_no', 'idx_after_sales_order_no');
            $table->index('status', 'idx_after_sales_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('after_sales');
    }
};
