<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_shops', function (Blueprint $table) {
            $table->id()->comment('店铺主键');
            $table->foreignId('customer_id')->constrained('customers')->comment('客户ID');
            $table->tinyInteger('platform')->comment('平台类型: 1=淘宝/2=拼多多/3=阿里/4=抖音/5=京东');
            $table->string('shop_name', 128)->comment('店铺名称');
            $table->tinyInteger('shop_auth_status')->default(0)->comment('0=未授权/1=授权中/2=授权成功/3=授权失效');
            $table->string('auth_token', 512)->nullable()->comment('授权Token');
            $table->timestamp('expire_time')->nullable()->comment('授权过期时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_customer_id');
            $table->index('platform', 'idx_platform');
            $table->index('shop_auth_status', 'idx_shop_auth_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_shops');
    }
};
