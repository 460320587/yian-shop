<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->comment('操作人ID');
            $table->string('admin_name', 100)->nullable()->comment('操作人名称');
            $table->string('action', 50)->comment('操作类型');
            $table->string('model_type', 100)->nullable()->comment('操作对象类型');
            $table->unsignedBigInteger('model_id')->nullable()->comment('操作对象ID');
            $table->json('before_data')->nullable()->comment('操作前数据');
            $table->json('after_data')->nullable()->comment('操作后数据');
            $table->string('ip', 45)->nullable()->comment('IP地址');
            $table->string('user_agent', 500)->nullable()->comment('User-Agent');
            $table->tinyInteger('result')->default(1)->comment('结果: 1成功, 0失败');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->timestamps();

            $table->index(['admin_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
