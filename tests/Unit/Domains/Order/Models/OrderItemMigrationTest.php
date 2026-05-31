<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\Models;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderItemMigrationTest extends TestCase
{
    /** @test */
    public function order_items_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('order_items'));

        $expected = [
            'id', 'order_id', 'product_id', 'product_name', 'spec_info',
            'quantity', 'unit_price', 'total_price', 'file_url',
            'created_at', 'updated_at', 'deleted_at',
        ];

        foreach ($expected as $column) {
            $this->assertTrue(Schema::hasColumn('order_items', $column), "Missing column: {$column}");
        }
    }
}
