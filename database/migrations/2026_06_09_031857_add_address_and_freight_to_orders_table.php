<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('address_id')->nullable()->after('customer_id')
                ->comment('收货地址ID（快照关联）');
            $table->string('receiver_name', 50)->nullable()->after('address_id')
                ->comment('收货人姓名快照');
            $table->string('receiver_phone', 20)->nullable()->after('receiver_name')
                ->comment('收货人手机快照');
            $table->string('province', 50)->nullable()->after('receiver_phone')
                ->comment('省快照');
            $table->string('city', 50)->nullable()->after('province')
                ->comment('市快照');
            $table->string('county', 50)->nullable()->after('city')
                ->comment('区县快照');
            $table->string('detail_address', 255)->nullable()->after('county')
                ->comment('详细地址快照');
            $table->unsignedBigInteger('freight_amount')->default(0)->after('discount_sum')
                ->comment('运费金额 分');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'address_id',
                'receiver_name',
                'receiver_phone',
                'province',
                'city',
                'county',
                'detail_address',
                'freight_amount',
            ]);
        });
    }
};
