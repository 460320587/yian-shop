<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('admin_roles')->onDelete('set null')->comment('角色ID');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
