<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ink_coverage_checks', function (Blueprint $table): void {
            $table->id()->comment('检测主键');
            $table->foreignId('order_id')->constrained('orders')->comment('订单ID');
            $table->foreignId('file_id')->constrained('order_files')->comment('文件ID');
            $table->tinyInteger('check_type')->comment('检测类型: 1=预检 2=印刷前检测 3=印刷后抽检');
            $table->string('ink_type', 32)->nullable()->comment('油墨类型:CMYK/专色');
            $table->decimal('coverage_c', 5, 2)->nullable()->comment('C色覆盖率(%)');
            $table->decimal('coverage_m', 5, 2)->nullable()->comment('M色覆盖率(%)');
            $table->decimal('coverage_y', 5, 2)->nullable()->comment('Y色覆盖率(%)');
            $table->decimal('coverage_k', 5, 2)->nullable()->comment('K色覆盖率(%)');
            $table->decimal('total_coverage', 5, 2)->nullable()->comment('总覆盖率(%)');
            $table->tinyInteger('check_result')->nullable()->comment('1=合格 0=不合格');
            $table->json('check_report')->nullable()->comment('检测报告详情');
            $table->foreignId('checked_by')->nullable()->constrained('users')->comment('检测人ID');
            $table->timestamp('checked_at')->nullable()->comment('检测时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_id', 'idx_order_id');
            $table->index('file_id', 'idx_file_id');
            $table->index('check_type', 'idx_check_type');
            $table->index('checked_at', 'idx_checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ink_coverage_checks');
    }
};
