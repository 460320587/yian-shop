<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id()->comment('文章主键');
            $table->unsignedBigInteger('category_id')->comment('分类ID');
            $table->string('title', 255)->comment('标题');
            $table->text('content')->comment('正文');
            $table->string('summary', 500)->nullable()->comment('摘要');
            $table->string('author', 64)->nullable()->comment('作者');
            $table->json('tags')->nullable()->comment('标签');
            $table->string('cover_image', 500)->nullable()->comment('封面图');
            $table->integer('view_count')->default(0)->comment('浏览次数');
            $table->integer('like_count')->default(0)->comment('点赞数');
            $table->tinyInteger('publish_status')->default(1)->comment('1=已发布 0=草稿');
            $table->timestamp('published_at')->nullable()->comment('发布时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id', 'idx_category_id');
            $table->index('publish_status', 'idx_publish_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_articles');
    }
};
