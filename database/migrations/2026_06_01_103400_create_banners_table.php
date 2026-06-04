<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->string('title', 200)->nullable()->comment('标题');
            $table->string('image', 500)->comment('PC端图片URL');
            $table->string('image_mobile', 500)->nullable()->comment('移动端图片URL');
            $table->string('link_type', 20)->default('url')->comment('链接类型: product/category/url');
            $table->string('link_target', 500)->comment('跳转目标');
            $table->string('position', 50)->default('home')->comment('展示位置: home/category等');
            $table->unsignedInteger('sort')->default(0)->comment('排序，越小越靠前');
            $table->timestamp('display_start')->nullable()->comment('展示开始时间');
            $table->timestamp('display_end')->nullable()->comment('展示结束时间');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0:禁用 1:启用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('position', 'idx_banners_position');
            $table->index('status', 'idx_banners_status');
            $table->index('sort', 'idx_banners_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
