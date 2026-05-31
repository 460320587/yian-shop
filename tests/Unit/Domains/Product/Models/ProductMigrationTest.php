<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Product\Models;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductMigrationTest extends TestCase
{
    /** @test */
    public function products_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('products'));

        $expected = [
            'id', 'category_id', 'name', 'code', 'price_min', 'price_max',
            'status', 'sort', 'cover_image',
            'created_at', 'updated_at', 'deleted_at',
        ];

        foreach ($expected as $column) {
            $this->assertTrue(Schema::hasColumn('products', $column), "Missing column: {$column}");
        }
    }
}
