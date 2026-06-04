<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReorderTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_reorder_from_history_order(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Completed->value,
        ]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '宣传册',
            'status' => 1,
            'price_min' => 500,
            'price_max' => 1000,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => '宣传册',
            'quantity' => 2,
            'unit_price' => 500,
            'total_price' => 1000,
            'spec_info' => 'A4尺寸',
        ]);

        $response = $this->postJson('/api/v1/orders/' . $order->id . '/reorder');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('message', '已加入购物车');
    }

    public function test_reorder_copies_items_to_cart(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Completed->value,
        ]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '名片',
            'status' => 1,
            'price_min' => 300,
            'price_max' => 500,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => '名片',
            'quantity' => 5,
            'unit_price' => 300,
            'total_price' => 1500,
            'spec_info' => '铜版纸',
        ]);

        $this->postJson('/api/v1/orders/' . $order->id . '/reorder');

        $cart = Cart::where('customer_id', $customer->id)->first();
        $this->assertNotNull($cart);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 300,
            'subtotal' => 1500,
            'selected' => 1,
        ]);
    }

    public function test_reorder_skips_off_sale_products(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Completed->value,
        ]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '已下架商品',
            'status' => 2, // 下架
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => '已下架商品',
            'quantity' => 1,
            'unit_price' => 500,
            'total_price' => 500,
        ]);

        $response = $this->postJson('/api/v1/orders/' . $order->id . '/reorder');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_reorder_requires_order_ownership(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $otherCustomer->id,
            'status' => OrderStatus::Completed->value,
        ]);

        $response = $this->postJson('/api/v1/orders/' . $order->id . '/reorder');

        $response->assertStatus(403);
    }

    public function test_reorder_fails_for_nonexistent_order(): void
    {
        $this->authCustomer();

        $response = $this->postJson('/api/v1/orders/99999/reorder');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_reorder(): void
    {
        $response = $this->postJson('/api/v1/orders/1/reorder');
        $response->assertStatus(401);
    }
}
