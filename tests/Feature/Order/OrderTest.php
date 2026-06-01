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

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_authenticated_user_can_get_order_list(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_no' => 'Y20260601000001',
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $otherCustomer = Customer::factory()->create();
        Order::factory()->create([
            'customer_id' => $otherCustomer->id,
            'order_no' => 'Y20260601000002',
            'status' => OrderStatus::PendingPayment->value,
        ]);

        $response = $this->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.order_no', 'Y20260601000001');
    }

    public function test_guest_cannot_access_orders(): void
    {
        $response = $this->getJson('/api/v1/orders');
        $response->assertStatus(401);
    }

    public function test_user_can_create_order_from_cart(): void
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
        ]);

        $response = $this->postJson('/api/v1/orders', [
            'address_id' => 1,
            'invoice_type' => 0,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    'order_no',
                    'status',
                    'total_amount',
                    'items' => [
                        '*' => ['product_id', 'product_name', 'quantity', 'unit_price', 'subtotal'],
                    ],
                ],
            ])
            ->assertJsonPath('data.items.0.product_name', '宣传册')
            ->assertJsonPath('data.total_amount', 10);

        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_create_order_fails_with_empty_cart(): void
    {
        $customer = $this->authCustomer();
        Cart::factory()->create(['customer_id' => $customer->id]);

        $response = $this->postJson('/api/v1/orders', [
            'address_id' => 1,
            'invoice_type' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 3100);
    }

    public function test_user_can_get_order_detail(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_no' => 'Y20260601000003',
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => '名片']);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => '名片',
            'quantity' => 100,
            'unit_price' => 50,
            'total_price' => 5000,
        ]);

        $response = $this->getJson('/api/v1/orders/' . $order->id);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.order_no', 'Y20260601000003')
            ->assertJsonPath('data.items.0.product_name', '名片');
    }

    public function test_user_cannot_view_others_order_detail(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $otherCustomer->id,
            'order_no' => 'Y20260601000004',
        ]);

        $response = $this->getJson('/api/v1/orders/' . $order->id);
        $response->assertStatus(403);
    }

    public function test_user_can_cancel_pending_payment_order(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        $response = $this->putJson('/api/v1/orders/' . $order->id . '/cancel');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
        ]);
    }

    public function test_user_cannot_cancel_paid_order(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
        ]);

        $response = $this->putJson('/api/v1/orders/' . $order->id . '/cancel');

        $response->assertStatus(422)
            ->assertJsonPath('code', 4002);
    }

    public function test_cancel_order_requires_ownership(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $otherCustomer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        $response = $this->putJson('/api/v1/orders/' . $order->id . '/cancel');
        $response->assertStatus(403);
    }

    public function test_order_list_paginates_correctly(): void
    {
        $customer = $this->authCustomer();
        Order::factory()->count(15)->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        $response = $this->getJson('/api/v1/orders?per_page=10&page=2');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 15);
    }
}
