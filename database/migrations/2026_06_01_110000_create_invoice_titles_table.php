<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_titles', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('客户ID');
            $table->unsignedTinyInteger('title_type')->default(1)->comment('抬头类型 1:企业 2:个人');
            $table->unsignedTinyInteger('invoice_category')->default(1)->comment('发票分类 1:普票 2:专票');
            $table->string('company_name', 200)->comment('企业名称/个人姓名');
            $table->string('tax_number', 20)->nullable()->comment('纳税人识别号');
            $table->string('register_address', 255)->nullable()->comment('注册地址');
            $table->string('register_phone', 20)->nullable()->comment('注册电话');
            $table->string('bank_name', 100)->nullable()->comment('开户银行');
            $table->string('bank_account', 30)->nullable()->comment('银行账号');
            $table->unsignedTinyInteger('is_default')->default(0)->comment('是否默认 0:否 1:是');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_invoice_titles_customer_id');
            $table->index('is_default', 'idx_invoice_titles_is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_titles');
    }
};
