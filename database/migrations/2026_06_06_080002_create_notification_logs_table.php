<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->id()->comment('日志主键');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->comment('客户ID');
            $table->string('template_code', 64)->nullable()->comment('使用的模板编码');
            $table->tinyInteger('channel')->comment('发送渠道: 1站内信 2短信 3邮件 4微信 5推送 6电话');
            $table->string('type', 32)->comment('通知类型');
            $table->string('recipient', 128)->comment('收信人标识');
            $table->string('title', 255)->nullable()->comment('实际发送标题');
            $table->text('content')->comment('实际发送内容');
            $table->json('variables')->nullable()->comment('渲染变量原始值');
            $table->tinyInteger('status')->default(0)->comment('0待发送 1已发送 2失败 3已读 4去重 5屏蔽');
            $table->timestamp('sent_at')->nullable()->comment('发送成功时间');
            $table->timestamp('read_at')->nullable()->comment('阅读时间');
            $table->json('response')->nullable()->comment('第三方渠道返回的原始响应');
            $table->string('error_msg', 500)->nullable()->comment('失败原因');
            $table->tinyInteger('retry_count')->default(0)->comment('已重试次数');
            $table->string('dedup_key', 64)->nullable()->comment('去重键');
            $table->string('failover_from', 32)->nullable()->comment('本次发送是由哪个渠道failover而来');
            $table->unsignedBigInteger('aggregated_id')->nullable()->comment('聚合通知的主日志ID');
            $table->string('biz_id', 64)->nullable()->comment('业务唯一标识');
            $table->string('biz_type', 32)->nullable()->comment('业务类型');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_customer_id');
            $table->index('template_code', 'idx_template_code');
            $table->index('status', 'idx_status');
            $table->index('dedup_key', 'idx_dedup_key');
            $table->index('created_at', 'idx_created_at');
            $table->index(['customer_id', 'status', 'created_at'], 'idx_customer_status_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
