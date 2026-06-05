<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('param_templates', function (Blueprint $table): void {
            $table->id()->comment('参数模板主键');
            $table->foreignId('category_id')->constrained('product_categories')->comment('品类ID');
            $table->string('param_type', 32)->nullable()->comment('参数类型');
            $table->string('param_name', 64)->nullable()->comment('参数名称');
            $table->json('options')->nullable()->comment('选项配置');
            $table->json('rules')->nullable()->comment('联动规则');
            $table->integer('version')->default(1)->comment('版本号');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('1=启用 0=禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id', 'idx_category_id');
            $table->index(['category_id', 'version'], 'idx_category_version');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('param_templates');
    }
};
