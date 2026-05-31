<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('分类ID');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父分类ID 0=一级');
            $table->string('name', 50)->comment('分类名称');
            $table->string('icon', 200)->nullable()->comment('图标');
            $table->unsignedSmallInteger('sort')->default(0)->comment('排序');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0禁用1启用');
            $table->unsignedTinyInteger('level')->default(1)->comment('层级 1/2/3');
            $table->string('path', 100)->default('')->comment('层级路径');
            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_id', 'idx_parent_id');
            $table->index('status', 'idx_status');
            $table->index('sort', 'idx_sort');
            $table->index('level', 'idx_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
