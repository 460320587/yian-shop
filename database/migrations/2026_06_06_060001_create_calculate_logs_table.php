<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculate_logs', function (Blueprint $table) {
            $table->id()->comment('日志主键');
            $table->foreignId('order_id')->nullable()->constrained('orders')->comment('关联订单ID');
            $table->foreignId('product_id')->nullable()->constrained('products')->comment('关联商品ID');
            $table->json('params')->comment('计价输入参数（JSON）');
            $table->text('formula')->nullable()->comment('计价公式文本');
            $table->unsignedBigInteger('result')->comment('计价结果(分)');
            $table->timestamp('calculated_at')->useCurrent()->comment('计价时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_id', 'idx_order_id');
            $table->index('product_id', 'idx_product_id');
            $table->index('calculated_at', 'idx_calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculate_logs');
    }
};
