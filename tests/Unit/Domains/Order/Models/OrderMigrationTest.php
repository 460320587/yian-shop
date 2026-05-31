<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\Models;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderMigrationTest extends TestCase
{
    /** @test */
    public function orders_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('orders'));

        $expected = [
            'id', 'order_no', 'customer_id', 'status', 'out_status_name',
            'total_amount', 'deposit_sum', 'discount_sum', 'express_company',
            'delivery_type', 'source', 'remark', 'paid_at', 'submitted_at',
            'created_at', 'updated_at', 'deleted_at',
        ];

        foreach ($expected as $column) {
            $this->assertTrue(Schema::hasColumn('orders', $column), "Missing column: {$column}");
        }
    }
}
