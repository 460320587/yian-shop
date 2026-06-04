<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_deliveries', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()->comment('订单ID');
            $table->string('carrier_name', 50)->comment('承运商名称');
            $table->string('tracking_no', 50)->comment('快递单号');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1:已发货 2:运输中 3:已签收');
            $table->timestamp('shipped_at')->nullable()->comment('发货时间');
            $table->timestamp('delivered_at')->nullable()->comment('送达时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_id', 'idx_order_deliveries_order_id');
            $table->index('tracking_no', 'idx_order_deliveries_tracking_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_deliveries');
    }
};
