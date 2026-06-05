<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Logistics\Models\OrderDelivery;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLogisticsShipTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_ship_creates_order_delivery_with_tracking_no(): void
    {
        $this->authAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
            'out_status_name' => OrderStatus::Paid->label(),
        ]);

        $response = $this->putJson("/api/v1/admin/orders/{$order->id}/ship", [
            'express_company' => '顺丰速运',
            'tracking_no' => 'SF1234567890',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('message', '已发货');

        $order->refresh();
        $this->assertEquals(OrderStatus::Shipped->value, $order->status);

        $this->assertDatabaseHas('order_deliveries', [
            'order_id' => $order->id,
            'carrier_name' => '顺丰速运',
            'tracking_no' => 'SF1234567890',
            'status' => 1,
        ]);
    }

    public function test_ship_without_tracking_no_still_works(): void
    {
        $this->authAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
            'out_status_name' => OrderStatus::Paid->label(),
        ]);

        $response = $this->putJson("/api/v1/admin/orders/{$order->id}/ship", [
            'express_company' => '中通快递',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $order->refresh();
        $this->assertEquals(OrderStatus::Shipped->value, $order->status);
        $this->assertEquals('中通快递', $order->express_company);
    }

    public function test_ship_rejects_invalid_order_status(): void
    {
        $this->authAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        $response = $this->putJson("/api/v1/admin/orders/{$order->id}/ship", [
            'express_company' => '顺丰速运',
            'tracking_no' => 'SF1234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 4002);
    }
}
