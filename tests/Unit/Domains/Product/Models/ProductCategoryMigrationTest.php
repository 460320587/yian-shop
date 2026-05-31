<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Product\Models;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductCategoryMigrationTest extends TestCase
{
    /** @test */
    public function product_categories_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('product_categories'));

        $expected = [
            'id', 'parent_id', 'name', 'icon', 'sort', 'status', 'level', 'path',
            'created_at', 'updated_at', 'deleted_at',
        ];

        foreach ($expected as $column) {
            $this->assertTrue(Schema::hasColumn('product_categories', $column), "Missing column: {$column}");
        }
    }
}
