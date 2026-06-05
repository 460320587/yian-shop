<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->unsignedTinyInteger('rating')->default(5)->comment('评分 1-5');
            $table->text('content')->comment('评价内容');
            $table->json('images')->nullable()->comment('评价图片');
            $table->text('reply')->nullable()->comment('商家回复');
            $table->timestamp('reply_at')->nullable()->comment('回复时间');
            $table->boolean('is_show')->default(true)->comment('是否展示');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_show', 'created_at']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
