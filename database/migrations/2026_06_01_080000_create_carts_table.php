<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('购物车ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('客户ID');
            $table->unsignedInteger('total_count')->default(0)->comment('商品项总数');
            $table->unsignedBigInteger('selected_subtotal')->default(0)->comment('选中项小计 分');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('customer_id', 'uk_customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
