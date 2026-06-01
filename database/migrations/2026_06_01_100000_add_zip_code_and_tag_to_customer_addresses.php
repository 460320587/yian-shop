<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table): void {
            $table->string('zip_code', 10)->nullable()->after('detail_address')->comment('邮编');
            $table->string('tag', 20)->nullable()->after('is_default')->comment('标签');
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table): void {
            $table->dropColumn('zip_code', 'tag');
        });
    }
};
