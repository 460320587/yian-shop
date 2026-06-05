<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cache_locks')) {
            return;
        }

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255)->unique()->comment('锁唯一标识');
            $table->string('owner', 64)->comment('锁持有者');
            $table->timestamp('expires_at')->comment('过期时间');
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
    }
};
