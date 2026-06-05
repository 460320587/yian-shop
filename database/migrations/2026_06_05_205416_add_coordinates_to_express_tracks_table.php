<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('express_tracks', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable()->after('location')->comment('纬度');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude')->comment('经度');
        });
    }

    public function down(): void
    {
        Schema::table('express_tracks', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
