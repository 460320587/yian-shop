<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('券码');
            $table->string('name')->comment('券名称');
            $table->text('description')->nullable()->comment('券描述');
            $table->tinyInteger('type')->default(1)->comment('类型: 1=满减券 2=折扣券 3=直减券');
            $table->integer('value')->default(0)->comment('面值/折扣值(分或比例)');
            $table->integer('min_amount')->default(0)->comment('最低消费门槛(分), 0=无门槛');
            $table->integer('max_discount')->default(0)->comment('最大抵扣金额(分), 仅折扣券有效');
            $table->timestamp('start_at')->comment('有效期开始');
            $table->timestamp('end_at')->comment('有效期结束');
            $table->integer('total_count')->default(-1)->comment('总发行量, -1=不限');
            $table->integer('per_customer_limit')->default(-1)->comment('每人限领, -1=不限');
            $table->integer('claimed_count')->default(0)->comment('已领取数量');
            $table->integer('used_count')->default(0)->comment('已使用数量');
            $table->tinyInteger('status')->default(1)->comment('状态: 1=启用 2=停用');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
