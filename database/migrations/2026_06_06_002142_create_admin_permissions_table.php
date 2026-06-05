<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_permissions', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('权限ID');
            $table->string('name', 50)->comment('权限名称');
            $table->string('code', 100)->unique()->comment('权限代码');
            $table->string('group', 50)->default('')->comment('权限分组');
            $table->unsignedTinyInteger('type')->default(3)->comment('类型 1菜单2按钮3API4数据');
            $table->timestamps();

            $table->index('group', 'idx_admin_permissions_group');
            $table->index('type', 'idx_admin_permissions_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_permissions');
    }
};
