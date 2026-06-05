<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_wallets', function (Blueprint $table) {
            $table->id()->comment('钱包主键');
            $table->foreignId('customer_id')->unique()->constrained('customers')->comment('客户ID');
            $table->unsignedBigInteger('balance')->default(0)->comment('余额(分)');
            $table->unsignedBigInteger('frozen_amount')->default(0)->comment('冻结金额(分)');
            $table->unsignedBigInteger('total_recharge')->default(0)->comment('累计充值(分)');
            $table->unsignedBigInteger('total_consume')->default(0)->comment('累计消费(分)');
            $table->tinyInteger('status')->default(1)->comment('1=正常 0=冻结');
            $table->unsignedBigInteger('version')->default(0)->comment('乐观锁版本号');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_customer_id');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_wallets');
    }
};
