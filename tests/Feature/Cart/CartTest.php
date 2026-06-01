<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_authenticated_user_can_get_cart(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'status' => 1, 'name' => '宣传册']);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 500,
            'subtotal' => 1000,
            'selected' => 1,
        ]);

        $response = $this->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'items' => [
                        '*' => ['id', 'product_id', 'product_name', 'quantity', 'unit_price', 'subtotal', 'selected'],
                    ],
                    'summary' => ['total_count', 'selected_count', 'selected_subtotal'],
                ],
            ])
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.items.0.product_name', '宣传册')
            ->assertJsonPath('data.summary.total_count', 1)
            ->assertJsonPath('data.summary.selected_subtotal', 10);
    }

    public function test_guest_cannot_access_cart(): void
    {
        $response = $this->getJson('/api/v1/cart');
        $response->assertStatus(401);
    }

    public function test_user_can_add_product_to_cart(): void
    {
        $customer = $this->authCustomer();
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 1,
            'price_min' => 500,
            'price_max' => 1000,
        ]);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.quantity', 3);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
            'subtotal' => 1500,
        ]);
    }

    public function test_add_cart_requires_product_id(): void
    {
        $this->authCustomer();

        $response = $this->postJson('/api/v1/cart/items', [
            'quantity' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_add_cart_requires_valid_product(): void
    {
        $this->authCustomer();

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => 99999,
            'quantity' => 1,
        ]);

        $response->assertStatus(404);
    }

    public function test_add_cart_rejects_off_sale_product(): void
    {
        $this->authCustomer();
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'status' => 2]);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(404);
    }

    public function test_user_can_update_cart_item_quantity(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'status' => 1]);
        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 500,
            'subtotal' => 500,
        ]);

        $response = $this->putJson('/api/v1/cart/items/' . $item->id, [
            'quantity' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.quantity', 5)
            ->assertJsonPath('data.subtotal', 25);
    }

    public function test_user_can_update_cart_item_selected(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'status' => 1]);
        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'selected' => 1,
        ]);

        $response = $this->putJson('/api/v1/cart/items/' . $item->id, [
            'selected' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.selected', false);
    }

    public function test_user_cannot_update_others_cart_item(): void
    {
        $customer = $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $otherCart = Cart::factory()->create(['customer_id' => $otherCustomer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'status' => 1]);
        $item = CartItem::factory()->create([
            'cart_id' => $otherCart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->putJson('/api/v1/cart/items/' . $item->id, [
            'quantity' => 5,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_cart_item(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'status' => 1]);
        $item = CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $response = $this->deleteJson('/api/v1/cart/items/' . $item->id);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_user_can_clear_cart(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'status' => 1]);
        CartItem::factory()->count(3)->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $response = $this->deleteJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseCount('cart_items', 0);
    }
}
