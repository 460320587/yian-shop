<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->comment('用户ID');
            $table->foreignId('coupon_id')->constrained('coupons')->comment('优惠券ID');
            $table->string('code')->comment('券实例唯一码');
            $table->tinyInteger('status')->default(1)->comment('状态: 1=未使用 2=已使用 3=已过期 4=已作废');
            $table->timestamp('claimed_at')->nullable()->comment('领取时间');
            $table->timestamp('used_at')->nullable()->comment('使用时间');
            $table->timestamp('expired_at')->nullable()->comment('过期时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['coupon_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_coupons');
    }
};
