<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->string('title', 200)->comment('标题');
            $table->text('content')->comment('内容');
            $table->string('type', 20)->default('general')->comment('类型: general/legality/promotion');
            $table->unsignedTinyInteger('is_popup')->default(0)->comment('是否弹窗 0:否 1:是');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0:禁用 1:启用');
            $table->timestamp('display_start')->nullable()->comment('展示开始时间');
            $table->timestamp('display_end')->nullable()->comment('展示结束时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('type', 'idx_announcements_type');
            $table->index('status', 'idx_announcements_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
