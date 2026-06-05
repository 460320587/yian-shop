<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_badges', function (Blueprint $table): void {
            $table->id()->comment('角标主键');
            $table->foreignId('customer_id')->constrained('customers')->comment('客户ID');
            $table->string('notification_type', 32)->comment('通知类型: order/aftersale/system/message');
            $table->integer('unread_count')->default(0)->comment('未读数');
            $table->timestamp('last_read_time')->nullable()->comment('最后阅读时间');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['customer_id', 'notification_type'], 'uk_customer_notification_type');
            $table->index('unread_count', 'idx_unread_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_badges');
    }
};
