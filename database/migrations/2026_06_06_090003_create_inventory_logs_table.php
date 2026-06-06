<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table): void {
            $table->id()->comment('流水ID');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade')->comment('商品ID');
            $table->string('order_no', 20)->nullable()->comment('关联订单号');
            $table->unsignedTinyInteger('type')->comment('类型 1预占 2扣减 3释放 4返还 5盘点调整');
            $table->unsignedInteger('qty_before')->comment('变动前数量');
            $table->integer('qty_change')->comment('变动数量(正增负减)');
            $table->unsignedInteger('qty_after')->comment('变动后数量');
            $table->string('reason', 200)->nullable()->comment('变动原因');
            $table->foreignId('created_by')->nullable()->constrained('admins')->onDelete('set null')->comment('操作人');
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id', 'idx_inventory_logs_product_id');
            $table->index('order_no', 'idx_inventory_logs_order_no');
            $table->index('type', 'idx_inventory_logs_type');
            $table->index('created_at', 'idx_inventory_logs_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};
