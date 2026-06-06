<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploads', function (Blueprint $table): void {
            $table->id()->comment('上传ID');
            $table->foreignId('user_id')->nullable()->constrained('customers')->onDelete('set null')->comment('上传用户');
            $table->string('purpose', 30)->comment('用途 product_image/user_file/prepress_pdf');
            $table->string('original_name', 255)->comment('原始文件名');
            $table->string('storage_path', 500)->comment('存储路径');
            $table->string('url', 500)->comment('访问URL');
            $table->unsignedBigInteger('file_size')->default(0)->comment('字节');
            $table->string('mime_type', 100)->comment('MIME类型');
            $table->string('extension', 20)->comment('扩展名');
            $table->unsignedInteger('width')->nullable()->comment('图片宽');
            $table->unsignedInteger('height')->nullable()->comment('图片高');
            $table->string('hash_md5', 32)->nullable()->comment('MD5');
            $table->unsignedTinyInteger('is_virus_scanned')->default(0)->comment('1:已扫描');
            $table->string('virus_scan_result', 50)->nullable()->comment('clean/infected/error');
            $table->unsignedTinyInteger('status')->default(1)->comment('1:有效 0:已删除');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id', 'idx_uploads_user_id');
            $table->index('purpose', 'idx_uploads_purpose');
            $table->index('hash_md5', 'idx_uploads_hash_md5');
            $table->index('status', 'idx_uploads_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
