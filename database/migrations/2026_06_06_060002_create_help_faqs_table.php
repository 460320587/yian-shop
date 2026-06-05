<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_faqs', function (Blueprint $table) {
            $table->id()->comment('FAQ主键');
            $table->unsignedBigInteger('category_id')->comment('分类ID');
            $table->string('question', 500)->comment('问题');
            $table->text('answer')->comment('答案');
            $table->string('keywords', 255)->nullable()->comment('关键词');
            $table->integer('view_count')->default(0)->comment('浏览次数');
            $table->integer('helpful_count')->default(0)->comment('有帮助数');
            $table->integer('not_helpful_count')->default(0)->comment('无帮助数');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('1=显示 0=隐藏');
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id', 'idx_category_id');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_faqs');
    }
};
