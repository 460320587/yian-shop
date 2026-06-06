<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carriers', function (Blueprint $table): void {
            $table->id()->comment('承运商ID');
            $table->string('name', 50)->comment('承运商名称 顺丰/中通/圆通');
            $table->string('code', 20)->unique()->comment('编码 sf/zto/yto');
            $table->string('api_type', 20)->comment('API类型 kdniao/kuaidi100/official');
            $table->json('config')->nullable()->comment('API配置');
            $table->unsignedTinyInteger('is_default')->default(0)->comment('1:默认承运商');
            $table->unsignedTinyInteger('status')->default(1)->comment('1:启用 0:禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('code', 'idx_carriers_code');
            $table->index('status', 'idx_carriers_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carriers');
    }
};
