<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->id()->comment('资料ID');
            $table->foreignId('user_id')->unique()->constrained('customers')->onDelete('cascade')->comment('客户ID');
            $table->string('real_name', 50)->nullable()->comment('真实姓名');
            $table->unsignedTinyInteger('gender')->nullable()->comment('0女 1男 2保密');
            $table->date('birthday')->nullable()->comment('生日');
            $table->string('id_card', 18)->nullable()->comment('身份证号');
            $table->string('industry', 50)->nullable()->comment('所属行业');
            $table->string('position', 50)->nullable()->comment('职位');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id', 'idx_user_profiles_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
