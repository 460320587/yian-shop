<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table): void {
            $table->id()->comment('设备ID');
            $table->foreignId('user_id')->constrained('customers')->onDelete('cascade')->comment('客户ID');
            $table->string('device_id', 64)->comment('设备指纹');
            $table->string('device_name', 100)->nullable()->comment('设备名称');
            $table->string('platform', 20)->nullable()->comment('ios/android/web/wxh5');
            $table->string('ip_address', 45)->nullable()->comment('IP地址');
            $table->timestamp('last_active_at')->nullable()->comment('最后活跃时间');
            $table->unsignedTinyInteger('is_current')->default(0)->comment('1:当前会话设备');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id', 'idx_user_devices_user_id');
            $table->index('device_id', 'idx_user_devices_device_id');
            $table->unique(['user_id', 'device_id'], 'uk_user_devices_user_device');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
