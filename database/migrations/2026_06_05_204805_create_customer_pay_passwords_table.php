<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_pay_passwords', function (Blueprint $table): void {
            $table->id()->comment('支付密码ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('客户ID');
            $table->string('pay_password_hash', 255)->comment('支付密码 bcrypt 哈希');
            $table->unsignedTinyInteger('fail_count')->default(0)->comment('连续失败次数');
            $table->timestamp('locked_until')->nullable()->comment('锁定截止时间');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('customer_id', 'uk_customer_id');
            $table->index('locked_until', 'idx_locked_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_pay_passwords');
    }
};
