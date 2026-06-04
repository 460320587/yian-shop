<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('管理员ID');
            $table->string('username', 50)->unique()->comment('用户名');
            $table->string('password', 255)->comment('密码');
            $table->string('real_name', 50)->comment('真实姓名');
            $table->string('phone', 20)->nullable()->comment('手机号');
            $table->string('email', 100)->nullable()->comment('邮箱');
            $table->string('role', 30)->comment('角色 super_admin/operator/customer_service/factory_manager/finance');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0禁用1启用');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录');
            $table->string('last_login_ip', 45)->nullable()->comment('最后登录IP');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('role', 'idx_admins_role');
            $table->index('status', 'idx_admins_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
