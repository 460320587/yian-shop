<?php

declare(strict_types=1);

namespace Tests\Feature\Sample;

use App\Domains\Product\Models\Product;
use App\Domains\Sample\Models\SampleOrder;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SampleOrderTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(array $attributes = []): Customer
    {
        $customer = Customer::factory()->create($attributes);
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_get_sample_order_list(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 1]);
        SampleOrder::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
        ]);
        $otherCustomer = Customer::factory()->create();
        SampleOrder::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->getJson('/api/v1/samples');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_list_can_filter_by_status(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 1]);
        SampleOrder::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'status' => 100,
        ]);
        SampleOrder::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'status' => 101,
        ]);

        $response = $this->getJson('/api/v1/samples?status=100');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_user_can_create_sample_order(): void
    {
        $customer = $this->authCustomer(['vip_level' => 0]);
        $product = Product::factory()->create(['status' => 1, 'price_min' => 5000]);

        $response = $this->postJson('/api/v1/samples/orders', [
            'product_id' => $product->id,
            'quantity' => 2,
            'remark' => '测试样品订单',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 100)
            ->assertJsonPath('data.quantity', 2);

        $this->assertDatabaseHas('sample_orders', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'status' => 100,
        ]);
    }

    public function test_create_order_applies_vip_discount(): void
    {
        $customer = $this->authCustomer(['vip_level' => 5]);
        $product = Product::factory()->create(['status' => 1, 'price_min' => 10000]);

        $response = $this->postJson('/api/v1/samples/orders', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0);

        $order = SampleOrder::where('customer_id', $customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(50.0, $order->discount_amount->toYuan());
    }

    public function test_user_can_get_sample_order_detail(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 1]);
        $order = SampleOrder::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
        ]);

        $response = $this->getJson('/api/v1/samples/orders/' . $order->id);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_user_cannot_view_others_sample_order(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $product = Product::factory()->create(['status' => 1]);
        $order = SampleOrder::factory()->create([
            'customer_id' => $otherCustomer->id,
            'product_id' => $product->id,
        ]);

        $response = $this->getJson('/api/v1/samples/orders/' . $order->id);

        $response->assertStatus(404);
    }

    public function test_create_order_rejects_off_sale_product(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 0]);

        $response = $this->postJson('/api/v1/samples/orders', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_create_order_requires_valid_product(): void
    {
        $this->authCustomer();

        $response = $this->postJson('/api/v1/samples/orders', [
            'product_id' => 99999,
            'quantity' => 1,
        ]);

        $response->assertStatus(422);
    }
}
