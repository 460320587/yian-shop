<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id()->comment('门店主键');
            $table->string('store_code', 32)->unique()->comment('门店编码');
            $table->string('store_name', 128)->comment('门店名称');
            $table->tinyInteger('store_type')->default(1)->comment('1=直营 2=加盟 3=合作');
            $table->string('province', 32)->nullable()->comment('省份');
            $table->string('city', 32)->nullable()->comment('城市');
            $table->string('district', 32)->nullable()->comment('区县');
            $table->string('address', 255)->nullable()->comment('详细地址');
            $table->decimal('longitude', 10, 7)->nullable()->comment('经度');
            $table->decimal('latitude', 10, 7)->nullable()->comment('纬度');
            $table->string('contact_phone', 20)->nullable()->comment('联系电话');
            $table->foreignId('manager_id')->nullable()->constrained('customers')->comment('负责人ID');
            $table->string('manager_name', 64)->nullable()->comment('负责人姓名');
            $table->string('coverage_area', 255)->nullable()->comment('服务覆盖区域描述');
            $table->string('business_hours', 64)->nullable()->comment('营业时间');
            $table->integer('capacity_daily')->default(0)->comment('日产能(单)');
            $table->integer('current_load')->default(0)->comment('当前负载');
            $table->tinyInteger('status')->default(1)->comment('1=营业 2=休息 0=关闭');
            $table->boolean('support_pickup')->default(true)->comment('是否支持自提');
            $table->boolean('support_delivery')->default(false)->comment('是否支持配送');
            $table->integer('delivery_range')->default(0)->comment('配送范围(米)');
            $table->unsignedBigInteger('factory_id')->nullable()->comment('关联工厂ID');
            $table->timestamps();
            $table->softDeletes();

            $table->index('store_code', 'idx_store_code');
            $table->index('city', 'idx_city');
            $table->index('status', 'idx_status');
            $table->index(['longitude', 'latitude'], 'idx_location');
            $table->index('factory_id', 'idx_factory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
