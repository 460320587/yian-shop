<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_schedules', function (Blueprint $table) {
            $table->id()->comment('排期主键');
            $table->foreignId('order_id')->constrained('orders')->comment('订单ID');
            $table->unsignedBigInteger('factory_id')->nullable()->comment('工厂ID');
            $table->date('schedule_date')->comment('排期日期');
            $table->time('start_time')->nullable()->comment('开始时间');
            $table->time('end_time')->nullable()->comment('结束时间');
            $table->string('process_name', 64)->nullable()->comment('工序名称');
            $table->unsignedBigInteger('equipment_id')->nullable()->comment('设备ID');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作员ID');
            $table->tinyInteger('status')->default(0)->comment('0=待排 1=已排 2=生产中 3=已完成 4=延期');
            $table->tinyInteger('priority')->default(3)->comment('1=最高 5=最低');
            $table->decimal('estimated_hours', 6, 2)->nullable()->comment('预计工时');
            $table->decimal('actual_hours', 6, 2)->nullable()->comment('实际工时');
            $table->integer('progress')->default(0)->comment('进度百分比');
            $table->string('delay_reason', 255)->nullable()->comment('延期原因');
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_id', 'idx_order_id');
            $table->index('factory_id', 'idx_factory_id');
            $table->index('schedule_date', 'idx_schedule_date');
            $table->index('status', 'idx_status');
            $table->index(['factory_id', 'schedule_date'], 'idx_factory_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_schedules');
    }
};
