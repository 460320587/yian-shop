<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Queries;

use App\Infrastructure\Queries\BaseQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class BaseQueryTest extends TestCase
{
    public function test_base_query_is_abstract(): void
    {
        $reflection = new \ReflectionClass(BaseQuery::class);
        $this->assertTrue($reflection->isAbstract(), 'BaseQuery 必须是抽象类');
    }

    public function test_base_query_defines_abstract_methods(): void
    {
        $reflection = new \ReflectionClass(BaseQuery::class);
        $this->assertTrue($reflection->hasMethod('query'), '必须定义 query() 方法');
        $this->assertTrue($reflection->hasMethod('applyFilters'), '必须定义 applyFilters() 方法');
    }

    public function test_chainable_per_page(): void
    {
        $query = new class([]) extends BaseQuery {
            protected function query(): Builder { return \App\Domains\Order\Models\Order::query(); }
            protected function applyFilters(Builder $query): Builder { return $query; }
        };

        $result = $query->perPage(25);
        $this->assertSame($query, $result, 'perPage() 必须支持链式调用');
    }

    public function test_chainable_with(): void
    {
        $query = new class([]) extends BaseQuery {
            protected function query(): Builder { return \App\Domains\Order\Models\Order::query(); }
            protected function applyFilters(Builder $query): Builder { return $query; }
        };

        $result = $query->with(['customer']);
        $this->assertSame($query, $result, 'with() 必须支持链式调用');
    }

    public function test_chainable_order_by(): void
    {
        $query = new class([]) extends BaseQuery {
            protected function query(): Builder { return \App\Domains\Order\Models\Order::query(); }
            protected function applyFilters(Builder $query): Builder { return $query; }
        };

        $result = $query->orderBy('updated_at', 'asc');
        $this->assertSame($query, $result, 'orderBy() 必须支持链式调用');
    }

    public function test_default_sort_is_created_at_desc(): void
    {
        $query = new class([]) extends BaseQuery {
            protected function query(): Builder { return \App\Domains\Order\Models\Order::query(); }
            protected function applyFilters(Builder $query): Builder { return $query; }
        };

        $reflection = new \ReflectionClass($query);
        $sortField = $reflection->getProperty('sortField');
        $sortField->setAccessible(true);
        $this->assertSame('created_at', $sortField->getValue($query));

        $sortDirection = $reflection->getProperty('sortDirection');
        $sortDirection->setAccessible(true);
        $this->assertSame('desc', $sortDirection->getValue($query));
    }

    public function test_filters_override_sort_from_constructor(): void
    {
        $query = new class(['sort_field' => 'updated_at', 'sort_direction' => 'asc']) extends BaseQuery {
            protected function query(): Builder { return \App\Domains\Order\Models\Order::query(); }
            protected function applyFilters(Builder $query): Builder { return $query; }
        };

        $reflection = new \ReflectionClass($query);
        $sortField = $reflection->getProperty('sortField');
        $sortField->setAccessible(true);
        $this->assertSame('updated_at', $sortField->getValue($query));

        $sortDirection = $reflection->getProperty('sortDirection');
        $sortDirection->setAccessible(true);
        $this->assertSame('asc', $sortDirection->getValue($query));
    }

    public function test_invalid_sort_direction_fallbacks_to_desc(): void
    {
        $query = new class(['sort_direction' => 'invalid']) extends BaseQuery {
            protected function query(): Builder { return \App\Domains\Order\Models\Order::query(); }
            protected function applyFilters(Builder $query): Builder { return $query; }
        };

        $reflection = new \ReflectionClass($query);
        $sortDirection = $reflection->getProperty('sortDirection');
        $sortDirection->setAccessible(true);
        $this->assertSame('desc', $sortDirection->getValue($query));
    }
}
