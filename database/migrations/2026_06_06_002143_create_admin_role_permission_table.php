<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_role_permission', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->foreignId('role_id')->constrained('admin_roles')->cascadeOnDelete()->comment('角色ID');
            $table->foreignId('permission_id')->constrained('admin_permissions')->cascadeOnDelete()->comment('权限ID');
            $table->unsignedTinyInteger('data_scope')->default(1)->comment('数据范围 1全部2本部门3本人4自定义');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id'], 'uk_role_permission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_role_permission');
    }
};
