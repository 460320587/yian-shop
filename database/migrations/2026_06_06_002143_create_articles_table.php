<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('文章ID');
            $table->string('title', 200)->comment('标题');
            $table->string('slug', 200)->unique()->comment('URL别名');
            $table->unsignedTinyInteger('type')->default(1)->comment('类型 1新闻2公告3帮助4SEO文章');
            $table->longText('content')->comment('内容');
            $table->string('summary', 500)->nullable()->comment('摘要');
            $table->string('cover', 500)->nullable()->comment('封面图');
            $table->string('author', 50)->nullable()->comment('作者');
            $table->unsignedInteger('view_count')->default(0)->comment('浏览量');
            $table->unsignedSmallInteger('sort')->default(0)->comment('排序');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态 0草稿1已发布2已下线');
            $table->timestamp('published_at')->nullable()->comment('发布时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('type', 'idx_articles_type');
            $table->index('status', 'idx_articles_status');
            $table->index('sort', 'idx_articles_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
