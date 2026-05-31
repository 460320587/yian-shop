<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_brands', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('品牌资质ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('客户ID');
            $table->string('name', 100)->comment('品牌名称');
            $table->unsignedTinyInteger('type')->default(0)->comment('类型 0=商标注册证/1=营业执照/2=合同授权/3=印刷委托书');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态 0=待审核/1=已审核/2=已驳回');
            $table->string('entruster', 100)->nullable()->comment('委托方');
            $table->unsignedTinyInteger('valid_type')->default(0)->comment('有效期类型 0长期/1有期限');
            $table->date('valid_start')->nullable()->comment('有效期开始');
            $table->date('valid_end')->nullable()->comment('有效期结束');
            $table->string('attachment', 500)->nullable()->comment('附件URL');
            $table->string('reject_reason', 255)->nullable()->comment('驳回原因');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_customer_id');
            $table->index('type', 'idx_type');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_brands');
    }
};
