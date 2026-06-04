<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('restrict')
                ->comment('客户ID');
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->onDelete('set null')
                ->comment('关联订单ID');
            $table->string('ticket_no', 32)->unique()->comment('工单编号');
            $table->unsignedTinyInteger('type')->comment('类型 1:服务质量 2:产品质量 3:物流问题 4:价格争议 5:其他');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0:已取消 1:待分配 2:处理中 3:待确认 4:已完成 5:已关闭');
            $table->unsignedTinyInteger('priority')->default(3)->comment('优先级 1:紧急 2:高 3:中 4:低');
            $table->string('title', 200)->comment('标题');
            $table->text('content')->comment('问题描述');
            $table->json('images')->nullable()->comment('凭证图片');
            $table->string('expected_resolution', 255)->nullable()->comment('期望处理方式');
            $table->unsignedTinyInteger('satisfaction')->nullable()->comment('满意度 1-5');
            $table->text('remark')->nullable()->comment('处理结果备注');
            $table->unsignedBigInteger('processed_by')->nullable()->comment('处理人ID');
            $table->timestamp('processed_at')->nullable()->comment('开始处理时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_tickets_customer_id');
            $table->index('order_id', 'idx_tickets_order_id');
            $table->index('status', 'idx_tickets_status');
            $table->index('type', 'idx_tickets_type');
            $table->index('priority', 'idx_tickets_priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
