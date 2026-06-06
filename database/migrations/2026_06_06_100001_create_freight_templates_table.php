<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freight_templates', function (Blueprint $table): void {
            $table->id()->comment('模板ID');
            $table->string('name', 100)->comment('模板名称');
            $table->foreignId('carrier_id')->constrained('carriers')->onDelete('restrict')->comment('默认承运商');
            $table->unsignedTinyInteger('calculation_type')->default(1)->comment('1:按重量 2:按体积 3:按件数');
            $table->decimal('first_weight', 8, 3)->default(1.000)->comment('首重kg');
            $table->decimal('first_price', 10, 2)->default(0.00)->comment('首重价格');
            $table->decimal('continue_weight', 8, 3)->default(1.000)->comment('续重kg');
            $table->decimal('continue_price', 10, 2)->default(0.00)->comment('续重价格');
            $table->decimal('free_threshold', 12, 2)->nullable()->comment('包邮金额阈值');
            $table->json('regions')->nullable()->comment('适用地区 [{province, city, surcharge}]');
            $table->unsignedTinyInteger('status')->default(1)->comment('1:启用 0:禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('carrier_id', 'idx_freight_carrier_id');
            $table->index('status', 'idx_freight_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freight_templates');
    }
};
