<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Notification\Models\CustomerNotification;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_confirm_order_payment(): void
    {
        $this->authAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'out_status_name' => OrderStatus::PendingPayment->label(),
        ]);

        $response = $this->putJson("/api/v1/admin/orders/{$order->id}/confirm-payment");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('message', '已确认付款');

        $order->refresh();
        $this->assertEquals(OrderStatus::Paid->value, $order->status);
        $this->assertEquals(OrderStatus::Paid->label(), $order->out_status_name);
    }

    public function test_admin_can_ship_order(): void
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
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('message', '已发货');

        $order->refresh();
        $this->assertEquals(OrderStatus::Shipped->value, $order->status);
        $this->assertEquals(OrderStatus::Shipped->label(), $order->out_status_name);
        $this->assertEquals('顺丰速运', $order->express_company);
    }

    public function test_admin_can_complete_order(): void
    {
        $this->authAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Shipped->value,
            'out_status_name' => OrderStatus::Shipped->label(),
        ]);

        $response = $this->putJson("/api/v1/admin/orders/{$order->id}/complete");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('message', '订单已完成');

        $order->refresh();
        $this->assertEquals(OrderStatus::Completed->value, $order->status);
        $this->assertEquals(OrderStatus::Completed->label(), $order->out_status_name);
    }

    public function test_admin_cannot_confirm_nonexistent_order(): void
    {
        $this->authAdmin();

        $response = $this->putJson('/api/v1/admin/orders/99999/confirm-payment');

        $response->assertNotFound();
    }

    public function test_admin_cannot_ship_pending_payment_order(): void
    {
        $this->authAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        $response = $this->putJson("/api/v1/admin/orders/{$order->id}/ship", [
            'express_company' => '顺丰速运',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 4002);
    }

    public function test_status_change_triggers_notification(): void
    {
        $this->authAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'out_status_name' => OrderStatus::PendingPayment->label(),
        ]);

        $this->putJson("/api/v1/admin/orders/{$order->id}/confirm-payment");

        $this->assertDatabaseHas('customer_notifications', [
            'customer_id' => $customer->id,
            'type' => 'order',
            'title' => '订单已付款',
        ]);
    }

    public function test_confirm_payment_creates_status_log(): void
    {
        $admin = $this->authAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'out_status_name' => OrderStatus::PendingPayment->label(),
        ]);

        $this->putJson("/api/v1/admin/orders/{$order->id}/confirm-payment");

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::PendingPayment->value,
            'to_status' => OrderStatus::Paid->value,
            'operator_type' => 'admin',
            'operator_id' => $admin->id,
        ]);
    }

    public function test_ship_creates_status_log(): void
    {
        $admin = $this->authAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
            'out_status_name' => OrderStatus::Paid->label(),
        ]);

        $this->putJson("/api/v1/admin/orders/{$order->id}/ship", [
            'express_company' => '顺丰速运',
        ]);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Paid->value,
            'to_status' => OrderStatus::Shipped->value,
            'operator_type' => 'admin',
            'operator_id' => $admin->id,
        ]);
    }
}
