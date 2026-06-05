<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_accounts', function (Blueprint $table) {
            $table->id()->comment('子账号主键');
            $table->foreignId('parent_id')->constrained('customers')->comment('主账号ID');
            $table->string('username', 64)->comment('子账号用户名');
            $table->string('password_hash', 255)->nullable()->comment('密码哈希(bcrypt)');
            $table->string('link_person', 64)->nullable()->comment('联系人');
            $table->string('mobile_phone', 64)->nullable()->comment('手机号');
            $table->string('email', 128)->nullable()->comment('邮箱');
            $table->string('role', 32)->nullable()->comment('角色标识');
            $table->integer('sub_permission')->default(0)->comment('权限位掩码: 0=总账号 1=客服 2=设计 4=下单 8=售后 16=财务');
            $table->json('permissions_json')->nullable()->comment('权限JSON数组');
            $table->tinyInteger('status')->default(1)->comment('1=启用 0=禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_id', 'idx_parent_id');
            $table->index('status', 'idx_status');
            $table->index(['parent_id', 'status'], 'idx_parent_status');
            $table->index('deleted_at', 'idx_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_accounts');
    }
};
