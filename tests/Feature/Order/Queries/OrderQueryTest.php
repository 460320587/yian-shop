<?php

declare(strict_types=1);

namespace Tests\Feature\Order\Queries;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Queries\OrderQuery;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_query_returns_all_without_filters(): void
    {
        Order::factory()->count(3)->create();

        $result = (new OrderQuery())->get();

        $this->assertCount(3, $result);
    }

    public function test_filter_by_order_no(): void
    {
        Order::factory()->create(['order_no' => 'Y20260101000001']);
        Order::factory()->create(['order_no' => 'Y20260101000002']);

        $result = (new OrderQuery(['order_no' => '000001']))->get();

        $this->assertCount(1, $result);
        $this->assertSame('Y20260101000001', $result->first()->order_no);
    }

    public function test_filter_by_status(): void
    {
        Order::factory()->create(['status' => 10]);
        Order::factory()->create(['status' => 20]);

        $result = (new OrderQuery(['status' => 10]))->get();

        $this->assertCount(1, $result);
        $this->assertSame(10, $result->first()->status);
    }

    public function test_filter_by_customer_id(): void
    {
        $customer = Customer::factory()->create();
        Order::factory()->create(['customer_id' => $customer->id]);
        Order::factory()->create();

        $result = (new OrderQuery(['customer_id' => $customer->id]))->get();

        $this->assertCount(1, $result);
        $this->assertSame($customer->id, $result->first()->customer_id);
    }

    public function test_filter_by_amount_range(): void
    {
        Order::factory()->create(['total_amount' => 5000]);
        Order::factory()->create(['total_amount' => 15000]);
        Order::factory()->create(['total_amount' => 25000]);

        $result = (new OrderQuery(['min_amount' => 10000, 'max_amount' => 20000]))->get();

        $this->assertCount(1, $result);
        $this->assertSame(15000, $result->first()->total_amount->amount);
    }

    public function test_paginate_returns_length_aware_paginator(): void
    {
        Order::factory()->count(5)->create();

        $result = (new OrderQuery())->perPage(2)->paginate();

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertSame(2, $result->perPage());
        $this->assertSame(5, $result->total());
    }

    public function test_with_customer_preloads_relation(): void
    {
        Order::factory()->create();

        $result = (new OrderQuery())->with(['customer'])->first();

        $this->assertTrue($result->relationLoaded('customer'));
    }

    public function test_count_returns_total(): void
    {
        Order::factory()->count(4)->create();

        $count = (new OrderQuery())->count();

        $this->assertSame(4, $count);
    }
}
