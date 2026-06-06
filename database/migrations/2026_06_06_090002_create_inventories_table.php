<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table): void {
            $table->id()->comment('库存ID');
            $table->foreignId('product_id')->unique()->constrained('products')->onDelete('cascade')->comment('商品ID');
            $table->unsignedInteger('available_qty')->default(0)->comment('可用库存');
            $table->unsignedInteger('reserved_qty')->default(0)->comment('已预占库存');
            $table->unsignedInteger('locked_qty')->default(0)->comment('已锁定(生产中)');
            $table->unsignedInteger('safety_stock')->default(0)->comment('安全库存阈值');
            $table->unsignedInteger('version')->default(0)->comment('乐观锁版本号');
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id', 'idx_inventories_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
