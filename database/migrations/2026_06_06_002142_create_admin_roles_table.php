<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('角色ID');
            $table->string('name', 50)->comment('角色名称');
            $table->string('code', 50)->unique()->comment('角色代码');
            $table->string('description', 200)->nullable()->comment('描述');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0禁用1启用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'idx_admin_roles_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }
};
