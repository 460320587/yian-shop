<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\ProductionSchedule;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderProductionScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer, 'sanctum');
        return $customer;
    }

    public function test_user_can_view_order_production_schedule(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'status' => OrderStatus::InProduction->value]);
        ProductionSchedule::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->getJson("/api/v1/orders/{$order->id}/production-schedule");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_id', 'schedule_date', 'process_name', 'status', 'progress', 'priority'],
                ],
            ]);
    }

    public function test_user_cannot_view_others_order_schedule(): void
    {
        $this->authCustomer();
        $otherOrder = Order::factory()->create(['status' => OrderStatus::InProduction->value]);
        ProductionSchedule::factory()->create(['order_id' => $otherOrder->id]);

        $response = $this->getJson("/api/v1/orders/{$otherOrder->id}/production-schedule");

        $response->assertStatus(403);
    }

    public function test_returns_empty_array_when_no_schedules(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $response = $this->getJson("/api/v1/orders/{$order->id}/production-schedule");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_guest_cannot_access_production_schedule(): void
    {
        $order = Order::factory()->create();

        $this->getJson("/api/v1/orders/{$order->id}/production-schedule")
            ->assertUnauthorized();
    }
}
