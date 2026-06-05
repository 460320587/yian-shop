<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('日志ID');
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete()->comment('支付ID');
            $table->string('payment_no', 32)->comment('支付单号');
            $table->string('event', 50)->comment('事件 create/callback/query/close/refund');
            $table->unsignedTinyInteger('from_status')->nullable()->comment('原状态');
            $table->unsignedTinyInteger('to_status')->nullable()->comment('新状态');
            $table->unsignedBigInteger('amount')->default(0)->comment('金额 分');
            $table->json('gateway_response')->nullable()->comment('网关响应');
            $table->timestamps();

            $table->index('payment_id', 'idx_payment_logs_payment_id');
            $table->index('payment_no', 'idx_payment_logs_payment_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
