<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->id()->comment('日志ID');
            $table->string('phone', 20)->comment('手机号');
            $table->string('template_code', 50)->nullable()->comment('短信模板CODE');
            $table->string('content', 500)->nullable()->comment('发送内容');
            $table->unsignedTinyInteger('type')->default(1)->comment('1:验证码 2:通知 3:营销');
            $table->unsignedTinyInteger('status')->default(0)->comment('0:失败 1:成功');
            $table->string('provider', 20)->default('aliyun')->comment('提供商 aliyun/tencent');
            $table->string('error_msg', 200)->nullable()->comment('失败原因');
            $table->string('ip_address', 45)->nullable()->comment('请求IP');
            $table->string('request_id', 100)->nullable()->comment('服务商请求ID');
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone', 'idx_sms_logs_phone');
            $table->index('type', 'idx_sms_logs_type');
            $table->index('status', 'idx_sms_logs_status');
            $table->index('created_at', 'idx_sms_logs_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
