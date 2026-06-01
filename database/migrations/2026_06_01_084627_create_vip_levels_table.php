<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_levels', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->unsignedTinyInteger('level')->unique()->comment('等级值 0-8');
            $table->string('name', 50)->comment('等级名称');
            $table->unsignedInteger('min_points')->default(0)->comment('最低成长值');
            $table->decimal('discount', 3, 2)->default(1.00)->comment('折扣率 0.85=85折');
            $table->string('icon', 500)->nullable()->comment('等级图标');
            $table->json('privileges')->nullable()->comment('特权列表');
            $table->timestamps();
            $table->softDeletes();

            $table->index('level', 'idx_vip_levels_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_levels');
    }
};
