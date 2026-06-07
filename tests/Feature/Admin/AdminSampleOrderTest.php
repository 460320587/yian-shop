<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Sample\Models\SampleOrder;
use App\Domains\User\Models\Customer;
use App\Support\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSampleOrderTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_sample_orders(): void
    {
        $this->authAdmin();
        SampleOrder::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/sample-orders');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_no', 'customer_id', 'product_id', 'status', 'total_amount'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_filter_sample_orders_by_status(): void
    {
        $this->authAdmin();
        SampleOrder::factory()->create(['status' => 100]);
        SampleOrder::factory()->create(['status' => 101]);
        SampleOrder::factory()->create(['status' => 102]);

        $response = $this->getJson('/api/v1/admin/sample-orders?status=101');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(101, $response->json('data.0.status'));
    }

    public function test_admin_can_search_sample_orders_by_customer_phone(): void
    {
        $this->authAdmin();
        $customer = Customer::factory()->create(['phone' => '13800138000']);
        SampleOrder::factory()->create(['customer_id' => $customer->id]);
        SampleOrder::factory()->create();

        $response = $this->getJson('/api/v1/admin/sample-orders?keyword=13800138000');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_admin_can_view_sample_order_detail(): void
    {
        $this->authAdmin();
        $order = SampleOrder::factory()->create();

        $response = $this->getJson("/api/v1/admin/sample-orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.order_no', $order->order_no)
            ->assertJsonStructure([
                'data' => ['id', 'order_no', 'customer_id', 'product_id', 'quantity', 'unit_price', 'total_amount', 'status', 'address_snapshot', 'remark', 'created_at'],
            ]);
    }

    public function test_admin_can_update_sample_order_status_to_shipped(): void
    {
        $this->authAdmin();
        $order = SampleOrder::factory()->create(['status' => 101]);

        $response = $this->putJson("/api/v1/admin/sample-orders/{$order->id}/status", [
            'status' => 103,
            'remark' => '已发货',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('sample_orders', [
            'id' => $order->id,
            'status' => 103,
            'shipped_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_admin_can_update_sample_order_status_to_completed(): void
    {
        $this->authAdmin();
        $order = SampleOrder::factory()->create(['status' => 103]);

        $response = $this->putJson("/api/v1/admin/sample-orders/{$order->id}/status", [
            'status' => 104,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('sample_orders', [
            'id' => $order->id,
            'status' => 104,
            'completed_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_admin_can_cancel_sample_order(): void
    {
        $this->authAdmin();
        $order = SampleOrder::factory()->create(['status' => 100]);

        $response = $this->putJson("/api/v1/admin/sample-orders/{$order->id}/status", [
            'status' => 105,
            'remark' => '客户要求取消',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('sample_orders', [
            'id' => $order->id,
            'status' => 105,
            'cancelled_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_admin_cannot_transition_to_invalid_status(): void
    {
        $this->authAdmin();
        $order = SampleOrder::factory()->create(['status' => 100]);

        $response = $this->putJson("/api/v1/admin/sample-orders/{$order->id}/status", [
            'status' => 104, // 待付款不能直接到已完成
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', ErrorCode::ORDER_STATUS_INVALID->value);
    }

    public function test_admin_can_delete_sample_order(): void
    {
        $this->authAdmin();
        $order = SampleOrder::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/sample-orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertSoftDeleted('sample_orders', ['id' => $order->id]);
    }

    public function test_admin_cannot_delete_nonexistent_sample_order(): void
    {
        $this->authAdmin();

        $response = $this->deleteJson('/api/v1/admin/sample-orders/99999');

        $response->assertStatus(404)
            ->assertJsonPath('code', ErrorCode::NOT_FOUND->value);
    }

    public function test_unauthenticated_cannot_access_sample_orders(): void
    {
        $this->getJson('/api/v1/admin/sample-orders')->assertUnauthorized();
    }
}
