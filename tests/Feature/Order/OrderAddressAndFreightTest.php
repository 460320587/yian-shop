<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Logistics\Models\Carrier;
use App\Domains\Logistics\Models\FreightTemplate;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAddressAndFreightTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);

        return $customer;
    }

    public function test_order_store_saves_address_snapshot(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 1,
            'price_min' => 500,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 500,
            'subtotal' => 1000,
            'selected' => 1,
        ]);

        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'contact_name' => '张三',
            'contact_phone' => '13800138000',
            'province_name' => '广东省',
            'city_name' => '深圳市',
            'county_name' => '南山区',
            'detail_address' => '科技园南路88号',
            'is_default' => true,
        ]);

        $response = $this->postJson('/api/v1/orders', [
            'address_id' => $address->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'address_id' => $address->id,
            'receiver_name' => '张三',
            'receiver_phone' => '13800138000',
            'province' => '广东省',
            'city' => '深圳市',
            'county' => '南山区',
            'detail_address' => '科技园南路88号',
        ]);
    }

    public function test_order_store_calculates_freight(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 1,
            'price_min' => 500,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 500,
            'subtotal' => 1500,
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

        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
        ]);

        $response = $this->postJson('/api/v1/orders', [
            'address_id' => $address->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.total_amount', 35);

        // 商品金额 1500 分 = 15 元，quantity = 3
        // 运费 = first_price(10) + continue_price(5) * (3 - 1) = 10 + 10 = 20 元 = 2000 分
        // total_amount = 1500 + 2000 = 3500 分 = 35 元
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'freight_amount' => 2000,
            'total_amount' => 3500,
        ]);
    }

    public function test_order_store_freight_is_zero_when_free_threshold_met(): void
    {
        $customer = $this->authCustomer();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 1,
            'price_min' => 5000,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 5000,
            'subtotal' => 10000,
            'selected' => 1,
        ]);

        $carrier = Carrier::factory()->create();
        FreightTemplate::factory()->create([
            'carrier_id' => $carrier->id,
            'first_price' => 10.0,
            'continue_price' => 5.0,
            'free_threshold' => 50.0, // 满50元免邮
            'status' => 1,
        ]);

        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
        ]);

        $response = $this->postJson('/api/v1/orders', [
            'address_id' => $address->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.total_amount', 100); // 100元，免邮

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'freight_amount' => 0,
            'total_amount' => 10000,
        ]);
    }

    public function test_order_store_fails_without_address_id(): void
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

        $response = $this->postJson('/api/v1/orders', []);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422)
            ->assertJsonPath('data.address_id.0', 'validation.required');
    }

    public function test_order_store_fails_with_invalid_address_id(): void
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

        $response = $this->postJson('/api/v1/orders', [
            'address_id' => 99999,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('code', 404);
    }

    public function test_order_store_fails_with_others_address(): void
    {
        $customer = $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $address = CustomerAddress::factory()->create([
            'customer_id' => $otherCustomer->id,
            'is_default' => true,
        ]);

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

        $response = $this->postJson('/api/v1/orders', [
            'address_id' => $address->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_order_detail_includes_address(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'receiver_name' => '李四',
            'receiver_phone' => '13900139000',
            'province' => '浙江省',
            'city' => '杭州市',
            'county' => '西湖区',
            'detail_address' => '文三路478号',
            'freight_amount' => 1000,
        ]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => '海报']);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => '海报',
            'quantity' => 10,
            'unit_price' => 100,
            'total_price' => 1000,
        ]);

        $response = $this->getJson('/api/v1/orders/' . $order->id);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.address.receiver_name', '李四')
            ->assertJsonPath('data.address.receiver_phone', '13900139000')
            ->assertJsonPath('data.address.province', '浙江省')
            ->assertJsonPath('data.address.city', '杭州市')
            ->assertJsonPath('data.address.county', '西湖区')
            ->assertJsonPath('data.address.detail_address', '文三路478号')
            ->assertJsonPath('data.address.full_address', '浙江省杭州市西湖区文三路478号')
            ->assertJsonPath('data.freight_amount', 10);
    }
}
