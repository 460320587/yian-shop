<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Coupon\Models\Coupon;
use App\Domains\Coupon\Models\CustomerCoupon;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_authenticated_user_can_preview_checkout(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '宣传册',
            'status' => 1,
            'price_min' => 500,
            'price_max' => 1000,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 500,
            'subtotal' => 1000,
            'selected' => 1,
        ]);

        $response = $this->postJson('/api/v1/cart/checkout');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    'items',
                    'address',
                    'coupons',
                    'summary' => [
                        'goods_amount',
                        'discount',
                        'total_amount',
                    ],
                ],
            ])
            ->assertJsonPath('data.items.0.product_name', '宣传册')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.summary.goods_amount', 10)
            ->assertJsonPath('data.summary.total_amount', 10);
    }

    public function test_checkout_returns_default_address(): void
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

        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'contact_name' => '张三',
            'contact_phone' => '13800138000',
            'province_name' => '广东省',
            'city_name' => '深圳市',
            'county_name' => '南山区',
            'detail_address' => '科技园',
            'is_default' => true,
        ]);

        $response = $this->postJson('/api/v1/cart/checkout');

        $response->assertStatus(200)
            ->assertJsonPath('data.address.contact_name', '张三')
            ->assertJsonPath('data.address.contact_phone', '13800138000')
            ->assertJsonPath('data.address.full_address', '广东省深圳市南山区科技园');
    }

    public function test_checkout_returns_available_coupons(): void
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

        $coupon = Coupon::factory()->create([
            'type' => 1,
            'value' => 200,
            'min_amount' => 0,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);

        CustomerCoupon::factory()->create([
            'customer_id' => $customer->id,
            'coupon_id' => $coupon->id,
            'status' => 1,
        ]);

        $response = $this->postJson('/api/v1/cart/checkout');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(1, 'data.coupons')
            ->assertJsonPath('data.coupons.0.status', 1);
    }

    public function test_checkout_calculates_summary_correctly(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $productA = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '商品A',
            'status' => 1,
        ]);
        $productB = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '商品B',
            'status' => 1,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $productA->id,
            'quantity' => 2,
            'unit_price' => 500,
            'subtotal' => 1000,
            'selected' => 1,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $productB->id,
            'quantity' => 1,
            'unit_price' => 300,
            'subtotal' => 300,
            'selected' => 1,
        ]);

        $response = $this->postJson('/api/v1/cart/checkout');

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.goods_amount', 13)
            ->assertJsonPath('data.summary.discount', 0)
            ->assertJsonPath('data.summary.total_amount', 13);
    }

    public function test_checkout_fails_with_empty_cart(): void
    {
        $customer = $this->authCustomer();
        Cart::factory()->create(['customer_id' => $customer->id]);

        $response = $this->postJson('/api/v1/cart/checkout');

        $response->assertStatus(422)
            ->assertJsonPath('code', 3100);
    }

    public function test_checkout_with_item_ids_filters_items(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $productA = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '商品A',
            'status' => 1,
        ]);
        $productB = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '商品B',
            'status' => 1,
        ]);
        $itemA = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $productA->id,
            'quantity' => 2,
            'unit_price' => 500,
            'subtotal' => 1000,
            'selected' => 1,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $productB->id,
            'quantity' => 1,
            'unit_price' => 300,
            'subtotal' => 300,
            'selected' => 1,
        ]);

        $response = $this->postJson('/api/v1/cart/checkout', [
            'item_ids' => [$itemA->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_name', '商品A')
            ->assertJsonPath('data.summary.goods_amount', 10);
    }

    public function test_guest_cannot_checkout(): void
    {
        $response = $this->postJson('/api/v1/cart/checkout');
        $response->assertStatus(401);
    }
}
