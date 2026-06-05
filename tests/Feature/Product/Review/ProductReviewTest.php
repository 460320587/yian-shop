<?php

declare(strict_types=1);

namespace Tests\Feature\Product\Review;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductReview;
use App\Domains\User\Models\Customer;
use App\Domains\Order\Enums\OrderStatus;
use App\Support\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_guest_can_get_product_reviews(): void
    {
        $product = Product::factory()->create(['status' => 1]);
        ProductReview::factory()->count(3)->create(['product_id' => $product->id, 'is_show' => true]);
        ProductReview::factory()->create(['product_id' => $product->id, 'is_show' => false]);

        $response = $this->getJson('/api/v1/products/' . $product->id . '/reviews');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_user_can_submit_review_for_completed_order(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 1]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Completed->value,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson('/api/v1/orders/' . $order->id . '/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
            'content' => '质量很好，印刷清晰',
            'images' => ['http://example.com/img1.jpg'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('product_reviews', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'content' => '质量很好，印刷清晰',
        ]);
    }

    public function test_user_cannot_review_uncompleted_order(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 1]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson('/api/v1/orders/' . $order->id . '/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
            'content' => '测试内容好评',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_review_nonexistent_order_item(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 1]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Completed->value,
        ]);

        $response = $this->postJson('/api/v1/orders/' . $order->id . '/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
            'content' => '测试内容好评',
        ]);

        $response->assertStatus(403)->assertJsonPath("code", ErrorCode::FORBIDDEN->value);;
    }

    public function test_user_cannot_review_others_order(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $product = Product::factory()->create(['status' => 1]);
        $order = Order::factory()->create([
            'customer_id' => $otherCustomer->id,
            'status' => OrderStatus::Completed->value,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson('/api/v1/orders/' . $order->id . '/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
            'content' => '测试内容好评',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_get_my_reviews(): void
    {
        $customer = $this->authCustomer();
        ProductReview::factory()->count(2)->create(['customer_id' => $customer->id]);
        ProductReview::factory()->create();

        $response = $this->getJson('/api/v1/my-reviews');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_review_requires_rating_and_content(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create(['status' => 1]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Completed->value,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson('/api/v1/orders/' . $order->id . '/reviews', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', ErrorCode::VALIDATION_ERROR->value);
    }
}
