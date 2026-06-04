<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('express_tracks', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->unsignedBigInteger('delivery_id')->comment('配送记录ID');
            $table->timestamp('track_time')->comment('轨迹时间');
            $table->string('location', 200)->nullable()->comment('地点');
            $table->string('description', 500)->comment('轨迹描述');
            $table->timestamps();

            $table->index('delivery_id', 'idx_express_tracks_delivery_id');
            // 外键在 order_deliveries 创建后手动添加
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('express_tracks');
    }
};
