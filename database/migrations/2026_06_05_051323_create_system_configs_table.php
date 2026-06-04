<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('config_key', 100)->unique()->comment('配置键');
            $table->text('config_value')->nullable()->comment('配置值');
            $table->string('type', 20)->default('string')->comment('类型: string/int/bool/json');
            $table->string('description', 255)->nullable()->comment('配置说明');
            $table->string('group', 50)->default('basic')->comment('配置分组');
            $table->timestamps();

            $table->index(['group', 'config_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configs');
    }
};
