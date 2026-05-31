<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Models;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerAddressMigrationTest extends TestCase
{
    /** @test */
    public function customer_addresses_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('customer_addresses'));

        $expected = [
            'id', 'customer_id', 'province_name', 'city_name', 'county_name',
            'detail_address', 'contact_name', 'contact_phone', 'is_default',
            'created_at', 'updated_at', 'deleted_at',
        ];

        foreach ($expected as $column) {
            $this->assertTrue(Schema::hasColumn('customer_addresses', $column), "Missing column: {$column}");
        }
    }
}
