<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id()->comment('地址ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('客户ID');
            $table->string('province_name', 50)->comment('省');
            $table->string('city_name', 50)->comment('市');
            $table->string('county_name', 50)->comment('区/县');
            $table->string('detail_address', 255)->comment('详细地址');
            $table->string('contact_name', 50)->comment('联系人');
            $table->string('contact_phone', 20)->comment('联系电话');
            $table->boolean('is_default')->default(false)->comment('是否默认');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id', 'idx_customer_id');
            $table->index(['customer_id', 'is_default'], 'idx_customer_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
