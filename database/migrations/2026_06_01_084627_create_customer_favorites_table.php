<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_favorites', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete()->comment('客户ID');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->comment('商品ID');
            $table->string('remark', 200)->nullable()->comment('收藏备注');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1:有效 0:失效');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['customer_id', 'product_id'], 'uk_customer_favorites_customer_product');
            $table->index('customer_id', 'idx_customer_favorites_customer_id');
            $table->index('product_id', 'idx_customer_favorites_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_favorites');
    }
};
