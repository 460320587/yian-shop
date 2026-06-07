<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\InkCoverageCheck;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderInkCoverageCheckTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer, 'sanctum');
        return $customer;
    }

    public function test_user_can_view_order_ink_coverage_checks(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'status' => OrderStatus::InProduction->value]);
        InkCoverageCheck::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->getJson("/api/v1/orders/{$order->id}/ink-coverage-checks");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_id', 'file_id', 'check_type', 'ink_type', 'coverage_c', 'coverage_m', 'coverage_y', 'coverage_k', 'total_coverage', 'check_result', 'check_report', 'checked_at'],
                ],
            ]);
    }

    public function test_user_cannot_view_others_order_ink_checks(): void
    {
        $this->authCustomer();
        $otherOrder = Order::factory()->create(['status' => OrderStatus::InProduction->value]);
        InkCoverageCheck::factory()->create(['order_id' => $otherOrder->id]);

        $response = $this->getJson("/api/v1/orders/{$otherOrder->id}/ink-coverage-checks");

        $response->assertStatus(403);
    }

    public function test_returns_empty_array_when_no_ink_checks(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $response = $this->getJson("/api/v1/orders/{$order->id}/ink-coverage-checks");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_guest_cannot_access_ink_coverage_checks(): void
    {
        $order = Order::factory()->create();

        $this->getJson("/api/v1/orders/{$order->id}/ink-coverage-checks")
            ->assertUnauthorized();
    }
}
