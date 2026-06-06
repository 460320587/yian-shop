<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Logistics\Models\Carrier;
use App\Domains\Logistics\Models\FreightTemplate;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPricingTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);

        return $customer;
    }

    public function test_pricing_returns_freight_for_cart_items(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create();
        Cart::factory()->create(['customer_id' => $customer->id]);
        CartItem::factory()->create([
            'cart_id' => Cart::where('customer_id', $customer->id)->first()->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'subtotal' => 2000,
        ]);

        $carrier = Carrier::factory()->create();
        FreightTemplate::factory()->create([
            'carrier_id' => $carrier->id,
            'calculation_type' => 1,
            'first_weight' => 1.0,
            'first_price' => 10.0,
            'continue_weight' => 1.0,
            'continue_price' => 5.0,
            'free_threshold' => null,
        ]);

        $response = $this->postJson('/api/v1/orders/pricing', [
            'address_id' => 1,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.freight_amount', 10)
            ->assertJsonStructure(['data' => ['freight_amount', 'free_threshold', 'carrier_name']]);
    }

    public function test_pricing_returns_zero_when_free_threshold_met(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create();
        Cart::factory()->create(['customer_id' => $customer->id]);
        CartItem::factory()->create([
            'cart_id' => Cart::where('customer_id', $customer->id)->first()->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'subtotal' => 100000,
        ]);

        $carrier = Carrier::factory()->create();
        FreightTemplate::factory()->create([
            'carrier_id' => $carrier->id,
            'first_price' => 10.0,
            'free_threshold' => 500,
        ]);

        $response = $this->postJson('/api/v1/orders/pricing', [
            'address_id' => 1,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.freight_amount', 0);
    }

    public function test_pricing_requires_address_id(): void
    {
        $customer = $this->authCustomer();

        $response = $this->postJson('/api/v1/orders/pricing', [
            'items' => [],
        ]);

        $response->assertStatus(422);
    }

    public function test_guest_cannot_access_pricing(): void
    {
        $response = $this->postJson('/api/v1/orders/pricing', [
            'address_id' => 1,
            'items' => [],
        ]);

        $response->assertStatus(401);
    }
}
