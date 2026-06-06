<?php

declare(strict_types=1);

namespace Tests\Feature\User\Queries;

use App\Domains\User\Models\Customer;
use App\Domains\User\Queries\CustomerQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_query_returns_all_without_filters(): void
    {
        Customer::factory()->count(3)->create();

        $result = (new CustomerQuery())->get();

        $this->assertCount(3, $result);
    }

    public function test_filter_by_keyword_phone(): void
    {
        Customer::factory()->create(['phone' => '13800138000', 'nickname' => 'foo']);
        Customer::factory()->create(['phone' => '13900139000', 'nickname' => 'bar']);

        $result = (new CustomerQuery(['keyword' => '138001']))->get();

        $this->assertCount(1, $result);
        $this->assertSame('13800138000', $result->first()->phone);
    }

    public function test_filter_by_keyword_nickname(): void
    {
        Customer::factory()->create(['phone' => '13800138000', 'nickname' => 'Alice']);
        Customer::factory()->create(['phone' => '13900139000', 'nickname' => 'Bob']);

        $result = (new CustomerQuery(['keyword' => 'Ali']))->get();

        $this->assertCount(1, $result);
        $this->assertSame('Alice', $result->first()->nickname);
    }

    public function test_filter_by_status(): void
    {
        Customer::factory()->create(['status' => 1]);
        Customer::factory()->create(['status' => 0]);

        $result = (new CustomerQuery(['status' => 1]))->get();

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->first()->status);
    }

    public function test_filter_by_vip_level(): void
    {
        Customer::factory()->create(['vip_level' => 3]);
        Customer::factory()->create(['vip_level' => 5]);

        $result = (new CustomerQuery(['vip_level' => 3]))->get();

        $this->assertCount(1, $result);
        $this->assertSame(3, $result->first()->vip_level);
    }

    public function test_paginate_returns_length_aware_paginator(): void
    {
        Customer::factory()->count(5)->create();

        $result = (new CustomerQuery())->perPage(2)->paginate();

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertSame(2, $result->perPage());
        $this->assertSame(5, $result->total());
    }

    public function test_count_returns_total(): void
    {
        Customer::factory()->count(4)->create();

        $count = (new CustomerQuery())->count();

        $this->assertSame(4, $count);
    }
}
