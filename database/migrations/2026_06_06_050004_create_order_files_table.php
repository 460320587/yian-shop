<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_files', function (Blueprint $table) {
            $table->id()->comment('文件主键');
            $table->foreignId('order_id')->constrained('orders')->comment('订单ID');
            $table->string('file_url', 500)->comment('文件URL');
            $table->string('thumb_url', 500)->nullable()->comment('缩略图URL');
            $table->integer('page_count')->default(0)->comment('页数');
            $table->decimal('ink_coverage', 5, 2)->nullable()->comment('油墨覆盖率(%)');
            $table->foreignId('brand_id')->nullable()->constrained('customer_brands')->comment('关联品牌ID');
            $table->string('file_name', 255)->nullable()->comment('文件名');
            $table->unsignedBigInteger('file_size')->nullable()->comment('文件大小(字节)');
            $table->string('file_type', 32)->nullable()->comment('文件类型');
            $table->string('archive_path', 500)->nullable()->comment('归档路径');
            $table->tinyInteger('archive_status')->default(0)->comment('归档状态: 0=未归档 1=已归档');
            $table->integer('version')->default(1)->comment('文件版本号，翻单修改时递增');
            $table->tinyInteger('status')->default(1)->comment('1=有效 0=删除');
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_id', 'idx_order_id');
            $table->index('brand_id', 'idx_brand_id');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_files');
    }
};
