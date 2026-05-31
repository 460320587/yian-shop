<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('客户ID');
            $table->string('phone', 20)->unique()->comment('手机号');
            $table->string('password', 255)->comment('密码bcrypt');
            $table->string('nickname', 50)->nullable()->comment('昵称');
            $table->string('avatar', 500)->nullable()->comment('头像URL');
            $table->unsignedTinyInteger('type')->default(3)->comment('客户类型 3~8');
            $table->unsignedTinyInteger('auth_status')->default(0)->comment('认证状态 0未认证1审核中2已通过3已拒绝');
            $table->unsignedTinyInteger('vip_level')->default(0)->comment('VIP等级 0-8');
            $table->unsignedInteger('grow_value')->default(0)->comment('成长值');
            $table->unsignedBigInteger('balance')->default(0)->comment('余额 分');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0禁用1正常');
            $table->string('link_person', 50)->nullable()->comment('联系人');
            $table->string('qq', 20)->nullable()->comment('QQ号');
            $table->string('register_ip', 45)->nullable()->comment('注册IP');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone', 'idx_phone');
            $table->index('type', 'idx_type');
            $table->index('status', 'idx_status');
            $table->index('vip_level', 'idx_vip_level');
            $table->index('auth_status', 'idx_auth_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
