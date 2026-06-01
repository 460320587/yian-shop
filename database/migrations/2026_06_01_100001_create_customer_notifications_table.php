<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_notifications', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('通知ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('客户ID');
            $table->string('type', 20)->comment('类型 order/payment/system/promotion');
            $table->string('title', 100)->comment('标题');
            $table->text('content')->comment('内容');
            $table->unsignedTinyInteger('is_read')->default(0)->comment('已读 0:否 1:是');
            $table->string('action_url', 255)->nullable()->comment('跳转链接');
            $table->string('action_text', 50)->nullable()->comment('按钮文字');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_customer_id');
            $table->index(['customer_id', 'is_read'], 'idx_customer_read');
            $table->index('type', 'idx_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notifications');
    }
};
