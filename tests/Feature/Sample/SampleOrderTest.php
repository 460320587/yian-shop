<?php

declare(strict_types=1);

namespace Tests\Feature\Sample;

use App\Domains\Product\Models\Product;
use App\Domains\Sample\Models\SampleOrder;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SampleOrderTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['*']);
        return $customer;
    }

    public function test_user_can_list_sample_orders(): void
    {
        $customer = $this->authCustomer();
        SampleOrder::factory()->count(3)->create(['customer_id' => $customer->id]);
        SampleOrder::factory()->create();

        $response = $this->getJson('/api/v1/samples');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_no', 'product_id', 'quantity', 'status', 'product'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_user_can_filter_sample_orders_by_status(): void
    {
        $customer = $this->authCustomer();
        SampleOrder::factory()->create(['customer_id' => $customer->id, 'status' => 100]);
        SampleOrder::factory()->create(['customer_id' => $customer->id, 'status' => 101]);

        $response = $this->getJson('/api/v1/samples?status=100');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(100, $response->json('data.0.status'));
    }

    public function test_user_can_create_sample_order(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 1, 'price_min' => 5000]);

        $response = $this->postJson('/api/v1/samples/orders', [
            'product_id' => $product->id,
            'quantity' => 2,
            'remark' => '急需样品确认',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.quantity', 2);

        $this->assertDatabaseHas('sample_orders', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'status' => 100,
        ]);
    }

    public function test_user_cannot_create_sample_order_for_offline_product(): void
    {
        $this->authCustomer();
        $product = Product::factory()->create(['status' => 0]);

        $response = $this->postJson('/api/v1/samples/orders', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 3001);
    }

    public function test_user_can_view_sample_order_detail(): void
    {
        $customer = $this->authCustomer();
        $order = SampleOrder::factory()->create(['customer_id' => $customer->id]);

        $response = $this->getJson("/api/v1/samples/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.order_no', $order->order_no)
            ->assertJsonStructure([
                'data' => ['id', 'order_no', 'product', 'quantity', 'unit_price', 'total_amount'],
            ]);
    }

    public function test_user_cannot_view_others_sample_order(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $order = SampleOrder::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->getJson("/api/v1/samples/orders/{$order->id}");

        $response->assertNotFound()
            ->assertJsonPath('code', 404);
    }

    public function test_unauthenticated_cannot_access_sample_orders(): void
    {
        $this->getJson('/api/v1/samples')
            ->assertUnauthorized();
    }
}
