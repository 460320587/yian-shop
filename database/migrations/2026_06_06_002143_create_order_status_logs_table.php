<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_logs', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('日志ID');
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()->comment('订单ID');
            $table->unsignedTinyInteger('from_status')->comment('原状态');
            $table->unsignedTinyInteger('to_status')->comment('新状态');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->foreignId('operator_id')->nullable()->constrained('admins')->onDelete('set null')->comment('操作人');
            $table->string('operator_type', 20)->default('admin')->comment('操作者类型 admin/system/customer');
            $table->timestamps();

            $table->index('order_id', 'idx_order_status_logs_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');
    }
};
