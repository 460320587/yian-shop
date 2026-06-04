<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('reset_token', 64)->nullable()->comment('密码重置令牌');
            $table->timestamp('reset_token_expires_at')->nullable()->comment('令牌过期时间');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['reset_token', 'reset_token_expires_at']);
        });
    }
};
