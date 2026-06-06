<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_change_logs', function (Blueprint $table): void {
            $table->id()->comment('变更日志主键');
            $table->string('table_name', 64)->comment('表名');
            $table->unsignedBigInteger('record_id')->comment('记录ID');
            $table->tinyInteger('action_type')->comment('操作类型: 1=INSERT 2=UPDATE 3=DELETE');
            $table->string('field_name', 64)->nullable()->comment('字段名');
            $table->text('old_value')->nullable()->comment('旧值');
            $table->text('new_value')->nullable()->comment('新值');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作人ID');
            $table->string('operator_name', 64)->nullable()->comment('操作人名称');
            $table->tinyInteger('operator_type')->default(1)->comment('1=用户 2=系统');
            $table->string('request_id', 64)->nullable()->comment('请求追踪ID');
            $table->string('ip_address', 64)->nullable()->comment('IP地址');
            $table->timestamp('created_at')->useCurrent()->comment('变更时间');
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            $table->index(['table_name', 'record_id'], 'idx_table_record');
            $table->index('operator_id', 'idx_operator_id');
            $table->index('created_at', 'idx_created_at');
            $table->index('request_id', 'idx_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_change_logs');
    }
};
