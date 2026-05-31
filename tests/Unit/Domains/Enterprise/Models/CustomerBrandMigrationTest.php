<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Enterprise\Models;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerBrandMigrationTest extends TestCase
{
    /** @test */
    public function customer_brands_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('customer_brands'));

        $expected = [
            'id', 'customer_id', 'name', 'type', 'status',
            'entruster', 'valid_type', 'valid_start', 'valid_end',
            'attachment', 'reject_reason',
            'created_at', 'updated_at', 'deleted_at',
        ];

        foreach ($expected as $column) {
            $this->assertTrue(Schema::hasColumn('customer_brands', $column), "Missing column: {$column}");
        }
    }
}
