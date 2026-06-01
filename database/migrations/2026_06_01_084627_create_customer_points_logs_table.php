<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_points_logs', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete()->comment('客户ID');
            $table->unsignedTinyInteger('type')->comment('类型 1:订单奖励 2:签到 3:活动 4:抵扣消费 5:过期扣除');
            $table->integer('points')->default(0)->comment('变动积分(正增负减)');
            $table->integer('balance_before')->comment('变动前余额');
            $table->integer('balance_after')->comment('变动后余额');
            $table->string('order_no', 20)->nullable()->comment('关联订单号');
            $table->string('remark', 200)->nullable()->comment('备注');
            $table->date('expired_at')->nullable()->comment('积分过期日');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_customer_points_logs_customer_id');
            $table->index('type', 'idx_customer_points_logs_type');
            $table->index('created_at', 'idx_customer_points_logs_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_points_logs');
    }
};
