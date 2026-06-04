<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_orders(): void
    {
        $this->authAdmin();
        Order::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/orders');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_no', 'customer_id', 'status', 'total_amount', 'customer'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_search_orders_by_order_no(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create(['order_no' => 'Y20260101000001']);
        Order::factory()->create();

        $response = $this->getJson('/api/v1/admin/orders?order_no=Y20260101000001');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Y20260101000001', $response->json('data.0.order_no'));
    }

    public function test_admin_can_filter_orders_by_status(): void
    {
        $this->authAdmin();
        Order::factory()->create(['status' => 10]);
        Order::factory()->create(['status' => 20]);

        $response = $this->getJson('/api/v1/admin/orders?status=10');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(10, $response->json('data.0.status'));
    }

    public function test_admin_can_filter_orders_by_customer_id(): void
    {
        $this->authAdmin();
        $customerA = Customer::factory()->create();
        $customerB = Customer::factory()->create();
        Order::factory()->create(['customer_id' => $customerA->id]);
        Order::factory()->create(['customer_id' => $customerB->id]);

        $response = $this->getJson("/api/v1/admin/orders?customer_id={$customerA->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($customerA->id, $response->json('data.0.customer_id'));
    }

    public function test_admin_can_view_order_detail(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();

        $response = $this->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.order_no', $order->order_no)
            ->assertJsonStructure([
                'data' => ['id', 'order_no', 'customer', 'items'],
            ]);
    }

    public function test_admin_cannot_view_nonexistent_order(): void
    {
        $this->authAdmin();

        $response = $this->getJson('/api/v1/admin/orders/99999');

        $response->assertNotFound()
            ->assertJsonPath('code', 404);
    }

    public function test_unauthenticated_cannot_access_orders(): void
    {
        $this->getJson('/api/v1/admin/orders')
            ->assertUnauthorized();
    }
}
