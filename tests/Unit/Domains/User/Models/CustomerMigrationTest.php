<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Models;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerMigrationTest extends TestCase
{
    /** @test */
    public function customers_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('customers'));

        $expected = [
            'id', 'phone', 'password', 'nickname', 'avatar', 'type',
            'auth_status', 'vip_level', 'grow_value', 'balance', 'status',
            'link_person', 'qq', 'register_ip', 'last_login_at',
            'created_at', 'updated_at', 'deleted_at',
        ];

        foreach ($expected as $column) {
            $this->assertTrue(Schema::hasColumn('customers', $column), "Missing column: {$column}");
        }
    }

    /** @test */
    public function customers_table_has_indexes(): void
    {
        $indexes = Schema::getIndexes('customers');
        $indexNames = array_column($indexes, 'name');

        $this->assertContains('customers_phone_unique', $indexNames);
        $this->assertContains('idx_phone', $indexNames);
        $this->assertContains('idx_type', $indexNames);
        $this->assertContains('idx_status', $indexNames);
    }
}
