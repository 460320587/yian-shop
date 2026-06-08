<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Domains\Logistics\Models\OrderDelivery;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderConfirmReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);

        return $customer;
    }

    public function test_user_can_confirm_receipt_for_shipped_order(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Shipped->value,
            'total_amount' => 5000,
        ]);
        OrderDelivery::factory()->create([
            'order_id' => $order->id,
            'carrier_name' => '顺丰速运',
            'tracking_no' => 'SF123456',
            'status' => 1,
            'shipped_at' => now()->subDays(3),
        ]);

        $response = $this->putJson('/api/v1/orders/' . $order->id . '/confirm-receipt');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Completed->value,
            'out_status_name' => '已完成',
        ]);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Shipped->value,
            'to_status' => OrderStatus::Completed->value,
            'operator_type' => 'customer',
        ]);
    }

    public function test_confirm_receipt_fails_for_non_shipped_order(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        $response = $this->putJson('/api/v1/orders/' . $order->id . '/confirm-receipt');

        $response->assertStatus(422)
            ->assertJsonPath('code', 4002);
    }

    public function test_confirm_receipt_fails_for_others_order(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $otherCustomer->id,
            'status' => OrderStatus::Shipped->value,
        ]);

        $response = $this->putJson('/api/v1/orders/' . $order->id . '/confirm-receipt');

        $response->assertStatus(403);
    }

    public function test_confirm_receipt_updates_delivery_delivered_at(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Shipped->value,
        ]);
        $delivery = OrderDelivery::factory()->create([
            'order_id' => $order->id,
            'status' => 1,
            'shipped_at' => now()->subDays(3),
            'delivered_at' => null,
        ]);

        $response = $this->putJson('/api/v1/orders/' . $order->id . '/confirm-receipt');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('order_deliveries', [
            'id' => $delivery->id,
        ]);
        $this->assertNotNull($delivery->fresh()->delivered_at);
    }

    public function test_guest_cannot_confirm_receipt(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Shipped->value,
        ]);

        $response = $this->putJson('/api/v1/orders/' . $order->id . '/confirm-receipt');

        $response->assertStatus(401);
    }
}
