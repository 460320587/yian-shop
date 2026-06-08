<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Domains\Coupon\Models\CustomerCoupon;
use App\Domains\Notification\Models\CustomerNotification;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductReview;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_get_dashboard_summary(): void
    {
        $customer = $this->authCustomer();

        Order::factory()->create(['customer_id' => $customer->id, 'status' => OrderStatus::PendingPayment->value]);
        Order::factory()->create(['customer_id' => $customer->id, 'status' => OrderStatus::PendingPayment->value]);
        Order::factory()->create(['customer_id' => $customer->id, 'status' => OrderStatus::Paid->value]);
        Order::factory()->create(['customer_id' => $customer->id, 'status' => OrderStatus::InProduction->value]);
        Order::factory()->create(['customer_id' => $customer->id, 'status' => OrderStatus::PendingDelivery->value]);
        Order::factory()->create(['customer_id' => $customer->id, 'status' => OrderStatus::PendingReceive->value]);
        $completedOrder = Order::factory()->create(['customer_id' => $customer->id, 'status' => OrderStatus::Completed->value]);
        $product = Product::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $completedOrder->id,
            'product_id' => $product->id,
            'product_name' => '测试商品',
            'quantity' => 1,
            'unit_price' => 1000,
            'total_price' => 1000,
        ]);

        CustomerNotification::factory()->count(3)->create(['customer_id' => $customer->id, 'is_read' => 0]);
        CustomerNotification::factory()->create(['customer_id' => $customer->id, 'is_read' => 1]);

        CustomerCoupon::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'status' => 1,
            'expired_at' => now()->addDay(),
        ]);
        CustomerCoupon::factory()->create([
            'customer_id' => $customer->id,
            'status' => 2,
            'used_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/user/dashboard');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    'order_status_counts' => [
                        'pending_payment',
                        'in_progress',
                        'pending_delivery',
                        'pending_receive',
                        'pending_review',
                        'completed',
                    ],
                    'unread_notification_count',
                    'available_coupon_count',
                    'recent_orders',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(2, $data['order_status_counts']['pending_payment']);
        $this->assertEquals(2, $data['order_status_counts']['in_progress']);
        $this->assertEquals(1, $data['order_status_counts']['pending_delivery']);
        $this->assertEquals(1, $data['order_status_counts']['pending_receive']);
        $this->assertEquals(1, $data['order_status_counts']['pending_review']);
        $this->assertEquals(1, $data['order_status_counts']['completed']);
        $this->assertEquals(3, $data['unread_notification_count']);
        $this->assertEquals(2, $data['available_coupon_count']);
        $this->assertCount(min(3, Order::where('customer_id', $customer->id)->count()), $data['recent_orders']);
    }

    public function test_dashboard_returns_empty_for_new_user(): void
    {
        $this->authCustomer();

        $response = $this->getJson('/api/v1/user/dashboard');

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $data = $response->json('data');
        $this->assertEquals(0, $data['order_status_counts']['pending_payment']);
        $this->assertEquals(0, $data['unread_notification_count']);
        $this->assertEquals(0, $data['available_coupon_count']);
        $this->assertEmpty($data['recent_orders']);
    }

    public function test_pending_review_excludes_already_reviewed_orders(): void
    {
        $customer = $this->authCustomer();
        $product = Product::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Completed->value,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => '测试商品',
            'quantity' => 1,
            'unit_price' => 1000,
            'total_price' => 1000,
        ]);

        // Before review: pending_review should be 1
        $response = $this->getJson('/api/v1/user/dashboard');
        $response->assertOk();
        $this->assertEquals(1, $response->json('data.order_status_counts.pending_review'));

        // After review: pending_review should be 0
        ProductReview::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'content' => '很好',
        ]);

        $response = $this->getJson('/api/v1/user/dashboard');
        $response->assertOk();
        $this->assertEquals(0, $response->json('data.order_status_counts.pending_review'));
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->getJson('/api/v1/user/dashboard')->assertUnauthorized();
    }
}
