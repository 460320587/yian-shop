<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_archives', function (Blueprint $table): void {
            $table->id()->comment('归档主键');
            $table->date('archive_date')->comment('归档日期');
            $table->string('storage_path', 500)->comment('归档文件存储路径');
            $table->string('format', 16)->comment('文件格式: parquet/sqlite/csv');
            $table->unsignedBigInteger('record_count')->default(0)->comment('归档记录数');
            $table->date('expire_date')->comment('归档过期日期');
            $table->tinyInteger('status')->default(0)->comment('0=归档中/1=已完成/2=失败/3=已删除');
            $table->timestamps();
            $table->softDeletes();

            $table->index('archive_date', 'idx_archive_date');
            $table->index('format', 'idx_format');
            $table->index('expire_date', 'idx_expire_date');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_archives');
    }
};
