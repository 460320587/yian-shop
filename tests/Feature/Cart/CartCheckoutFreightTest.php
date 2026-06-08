<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Logistics\Models\Carrier;
use App\Domains\Logistics\Models\FreightTemplate;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartCheckoutFreightTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);

        return $customer;
    }

    public function test_checkout_includes_freight_amount(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 1,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 500,
            'subtotal' => 1000,
            'selected' => 1,
        ]);

        $carrier = Carrier::factory()->create();
        FreightTemplate::factory()->create([
            'carrier_id' => $carrier->id,
            'first_price' => 10.0,
            'continue_price' => 5.0,
            'free_threshold' => null,
            'status' => 1,
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
        ]);

        $response = $this->postJson('/api/v1/cart/checkout');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.summary.goods_amount', 10)
            ->assertJsonPath('data.summary.freight_amount', 15) // first(10) + continue(5)*(2-1) = 15
            ->assertJsonPath('data.summary.total_amount', 25);
    }

    public function test_checkout_freight_is_zero_when_free_threshold_met(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 1,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
            'selected' => 1,
        ]);

        $carrier = Carrier::factory()->create();
        FreightTemplate::factory()->create([
            'carrier_id' => $carrier->id,
            'first_price' => 10.0,
            'continue_price' => 5.0,
            'free_threshold' => 50.0,
            'status' => 1,
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
        ]);

        $response = $this->postJson('/api/v1/cart/checkout');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.summary.goods_amount', 100)
            ->assertJsonPath('data.summary.freight_amount', 0)
            ->assertJsonPath('data.summary.total_amount', 100);
    }

    public function test_checkout_returns_zero_freight_when_no_template(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 1,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 500,
            'subtotal' => 500,
            'selected' => 1,
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
        ]);

        $response = $this->postJson('/api/v1/cart/checkout');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.summary.freight_amount', 0)
            ->assertJsonPath('data.summary.total_amount', 5);
    }
}
