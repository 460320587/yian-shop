<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table): void {
            $table->id()->comment('日志ID');
            $table->foreignId('user_id')->nullable()->constrained('customers')->onDelete('set null')->comment('客户ID，未登录时为NULL');
            $table->string('phone', 20)->nullable()->comment('尝试登录的手机号');
            $table->unsignedTinyInteger('type')->default(1)->comment('登录类型 1密码 2短信 3OAuth');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0失败 1成功');
            $table->string('fail_reason', 100)->nullable()->comment('失败原因代码');
            $table->string('ip_address', 45)->comment('IP地址');
            $table->string('user_agent', 500)->nullable()->comment('UA');
            $table->string('device_id', 64)->nullable()->comment('设备指纹');
            $table->string('location', 100)->nullable()->comment('IP归属地');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id', 'idx_login_logs_user_id');
            $table->index('phone', 'idx_login_logs_phone');
            $table->index('ip_address', 'idx_login_logs_ip');
            $table->index('created_at', 'idx_login_logs_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
