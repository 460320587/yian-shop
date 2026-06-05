<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderStatusLog;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusLogTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_order_status_change_creates_log(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        // 模拟状态变更（直接修改触发事件）
        $order->update(['status' => OrderStatus::Paid->value]);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::PendingPayment->value,
            'to_status' => OrderStatus::Paid->value,
        ]);
    }

    public function test_user_can_view_order_status_logs(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        OrderStatusLog::factory()->count(2)->create(['order_id' => $order->id]);
        OrderStatusLog::factory()->create(); // other order

        $response = $this->getJson("/api/v1/orders/{$order->id}/status-logs");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'from_status', 'to_status', 'remark', 'operator_type', 'created_at'],
                ],
            ]);
    }

    public function test_user_cannot_view_others_order_logs(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $otherCustomer->id]);
        OrderStatusLog::factory()->create(['order_id' => $order->id]);

        $response = $this->getJson("/api/v1/orders/{$order->id}/status-logs");

        $response->assertStatus(403);
    }
}
