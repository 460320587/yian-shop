<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_behavior_tracks', function (Blueprint $table): void {
            $table->id()->comment('埋点主键');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->comment('客户ID');
            $table->string('session_id', 64)->nullable()->comment('会话ID');
            $table->string('event_type', 64)->comment('事件类型:page_click/scroll/form_submit');
            $table->string('page_path', 255)->nullable()->comment('页面路径');
            $table->string('element_id', 128)->nullable()->comment('元素ID');
            $table->string('element_text', 255)->nullable()->comment('元素文本');
            $table->string('referrer', 500)->nullable()->comment('来源页面');
            $table->string('device_type', 32)->nullable()->comment('设备类型:pc/mobile/tablet');
            $table->string('browser', 64)->nullable()->comment('浏览器');
            $table->string('os', 32)->nullable()->comment('操作系统');
            $table->json('event_data')->nullable()->comment('事件附加数据');
            $table->timestamp('created_at')->useCurrent()->comment('记录时间');
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            $table->index('customer_id', 'idx_customer_id');
            $table->index('event_type', 'idx_event_type');
            $table->index('created_at', 'idx_created_at');
            $table->index('session_id', 'idx_session_id');
            $table->index(['event_type', 'created_at'], 'idx_event_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_behavior_tracks');
    }
};
