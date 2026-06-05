<?php

declare(strict_types=1);

namespace Tests\Feature\Logistics;

use App\Domains\Logistics\Models\ExpressTrack;
use App\Domains\Logistics\Models\OrderDelivery;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsMapTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_get_logistics_map(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $delivery = OrderDelivery::factory()->create(['order_id' => $order->id]);
        ExpressTrack::factory()->count(3)->create(['delivery_id' => $delivery->id]);

        $response = $this->getJson('/api/v1/logistics/' . $order->id . '/map');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.carrier_name', $delivery->carrier_name)
            ->assertJsonPath('data.tracking_no', $delivery->tracking_no)
            ->assertJsonCount(3, 'data.path')
            ->assertJsonStructure([
                'data' => [
                    'carrier_name',
                    'tracking_no',
                    'status',
                    'shipped_at',
                    'delivered_at',
                    'current' => ['time', 'location', 'latitude', 'longitude', 'description'],
                    'path' => [
                        ['time', 'location', 'latitude', 'longitude', 'description'],
                    ],
                ],
            ]);
    }

    public function test_map_returns_404_when_no_delivery(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $response = $this->getJson('/api/v1/logistics/' . $order->id . '/map');

        $response->assertStatus(404);
    }

    public function test_user_cannot_view_others_logistics_map(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->getJson('/api/v1/logistics/' . $order->id . '/map');

        $response->assertStatus(404);
    }
}
