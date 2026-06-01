<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_auths', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete()->comment('客户ID');
            $table->string('company_name', 200)->comment('企业全称');
            $table->string('credit_code', 50)->comment('统一社会信用代码');
            $table->string('legal_person', 50)->comment('法人姓名');
            $table->string('legal_person_id_card', 18)->comment('法人身份证号');
            $table->string('business_license_img', 500)->comment('营业执照图片URL');
            $table->string('contact_name', 50)->comment('联系人姓名');
            $table->string('contact_phone', 20)->comment('联系人电话');
            $table->string('register_address', 500)->nullable()->comment('注册地址');
            $table->string('office_address', 500)->nullable()->comment('办公地址');
            $table->date('valid_date')->nullable()->comment('营业执照有效期');
            $table->unsignedTinyInteger('auth_status')->default(1)->comment('认证状态 0未提交1审核中2已通过3不通过4已驳回20代认证');
            $table->string('audit_remark', 500)->nullable()->comment('审核备注');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('customer_id', 'uk_enterprise_auths_customer_id');
            $table->index('auth_status', 'idx_enterprise_auths_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_auths');
    }
};
