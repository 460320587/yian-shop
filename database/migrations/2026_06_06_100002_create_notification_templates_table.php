<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id()->comment('模板ID');
            $table->string('code', 50)->unique()->comment('模板编码');
            $table->string('name', 100)->comment('模板名称');
            $table->string('event', 50)->comment('触发事件');
            $table->json('channels')->comment('通道 [in_app, sms, email, wechat]');
            $table->string('sms_template_code', 50)->nullable()->comment('阿里云短信模板CODE');
            $table->string('email_subject', 200)->nullable()->comment('邮件主题');
            $table->text('email_body')->nullable()->comment('邮件内容HTML');
            $table->string('wechat_template_id', 50)->nullable()->comment('微信模板ID');
            $table->string('in_app_title', 200)->comment('APP内标题');
            $table->string('in_app_content', 1000)->comment('APP内内容');
            $table->unsignedTinyInteger('status')->default(1)->comment('1:启用 0:禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('code', 'idx_notif_tpl_code');
            $table->index('event', 'idx_notif_tpl_event');
            $table->index('status', 'idx_notif_tpl_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
