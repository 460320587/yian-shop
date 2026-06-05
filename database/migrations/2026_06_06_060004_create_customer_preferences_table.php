<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_preferences', function (Blueprint $table) {
            $table->id()->comment('偏好主键');
            $table->foreignId('customer_id')->unique()->constrained('customers')->comment('客户ID');
            $table->tinyInteger('product_layout_type')->default(1)->comment('产品布局: 1=列表/2=网格');
            $table->tinyInteger('category_grid_type')->default(1)->comment('分类展示: 1=大图/2=小图/3=文字');
            $table->tinyInteger('user_center_menu_fold')->default(0)->comment('0=展开/1=折叠');
            $table->tinyInteger('pay_now')->default(1)->comment('0=先下单后付/1=立即支付');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_customer_id');
            $table->index('product_layout_type', 'idx_product_layout_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_preferences');
    }
};
