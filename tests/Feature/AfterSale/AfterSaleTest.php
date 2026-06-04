<?php

declare(strict_types=1);

namespace Tests\Feature\AfterSale;

use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\AfterSale\Models\AfterSaleItem;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AfterSaleTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_get_after_sale_list(): void
    {
        $customer = $this->authCustomer();
        AfterSale::factory()->count(3)->create(['customer_id' => $customer->id]);
        $otherCustomer = Customer::factory()->create();
        AfterSale::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->getJson('/api/v1/after-sales');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_list_can_filter_by_status(): void
    {
        $customer = $this->authCustomer();
        AfterSale::factory()->count(2)->create(['customer_id' => $customer->id, 'status' => 1]);
        AfterSale::factory()->count(3)->create(['customer_id' => $customer->id, 'status' => 5]);

        $response = $this->getJson('/api/v1/after-sales?status=1');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_user_can_create_after_sale(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $product = Product::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson('/api/v1/after-sales', [
            'order_no' => $order->order_no,
            'type' => 1,
            'reason' => '商品质量问题',
            'description' => '收到的商品有破损',
            'items' => [
                [
                    'order_item_id' => $orderItem->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 1);

        $this->assertDatabaseHas('after_sales', [
            'order_no' => $order->order_no,
            'customer_id' => $customer->id,
            'status' => 1,
        ]);
    }

    public function test_user_can_get_after_sale_detail(): void
    {
        $customer = $this->authCustomer();
        $afterSale = AfterSale::factory()->create(['customer_id' => $customer->id]);
        AfterSaleItem::factory()->create(['after_sale_id' => $afterSale->id]);

        $response = $this->getJson('/api/v1/after-sales/' . $afterSale->id);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $afterSale->id);
    }

    public function test_user_can_cancel_after_sale(): void
    {
        $customer = $this->authCustomer();
        $afterSale = AfterSale::factory()->create([
            'customer_id' => $customer->id,
            'status' => 1,
        ]);

        $response = $this->putJson('/api/v1/after-sales/' . $afterSale->id . '/cancel');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $afterSale->refresh();
        $this->assertEquals(6, $afterSale->status);
    }

    public function test_user_cannot_cancel_completed_after_sale(): void
    {
        $customer = $this->authCustomer();
        $afterSale = AfterSale::factory()->create([
            'customer_id' => $customer->id,
            'status' => 5,
        ]);

        $response = $this->putJson('/api/v1/after-sales/' . $afterSale->id . '/cancel');

        $response->assertStatus(422);
    }

    public function test_user_cannot_view_others_after_sale(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $afterSale = AfterSale::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->getJson('/api/v1/after-sales/' . $afterSale->id);

        $response->assertStatus(404);
    }

    public function test_create_after_sale_requires_order_ownership(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $otherCustomer->id]);
        $product = Product::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson('/api/v1/after-sales', [
            'order_no' => $order->order_no,
            'type' => 1,
            'reason' => 'test',
            'items' => [
                [
                    'order_item_id' => $orderItem->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(403);
    }
}
