<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Domains\Order\Models\Order;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundRecordTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    private function createPaidOrderAndPayment(Customer $customer): array
    {
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 60,
            'total_amount' => 10000,
        ]);
        $payment = Payment::factory()->create([
            'order_no' => $order->order_no,
            'customer_id' => $customer->id,
            'amount' => 10000,
            'status' => PaymentStatus::Success->value,
            'paid_at' => now(),
        ]);
        return [$order, $payment];
    }

    public function test_user_can_get_refund_list(): void
    {
        $customer = $this->authCustomer();
        RefundRecord::factory()->count(2)->create(['customer_id' => $customer->id]);
        $otherCustomer = Customer::factory()->create();
        RefundRecord::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->getJson('/api/v1/refunds');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_user_can_create_refund(): void
    {
        $customer = $this->authCustomer();
        [$order, $payment] = $this->createPaidOrderAndPayment($customer);

        $response = $this->postJson('/api/v1/refunds', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 5000,
            'reason' => '商品缺货',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 0);

        $this->assertDatabaseHas('refund_records', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'customer_id' => $customer->id,
            'amount' => 5000,
            'reason' => '商品缺货',
            'status' => 0,
        ]);
    }

    public function test_user_can_get_refund_detail(): void
    {
        $customer = $this->authCustomer();
        $refund = RefundRecord::factory()->create(['customer_id' => $customer->id]);

        $response = $this->getJson('/api/v1/refunds/' . $refund->id);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $refund->id);
    }

    public function test_user_cannot_view_others_refund(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $refund = RefundRecord::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->getJson('/api/v1/refunds/' . $refund->id);

        $response->assertStatus(404);
    }

    public function test_create_refund_requires_owned_order(): void
    {
        $customer = $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        [$order, $payment] = $this->createPaidOrderAndPayment($otherCustomer);

        $response = $this->postJson('/api/v1/refunds', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 5000,
            'reason' => 'test',
        ]);

        $response->assertStatus(403);
    }

    public function test_create_refund_requires_valid_payment(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $otherCustomer = Customer::factory()->create();
        $payment = Payment::factory()->create([
            'order_no' => $order->order_no,
            'customer_id' => $otherCustomer->id,
            'status' => PaymentStatus::Success->value,
            'paid_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/refunds', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 5000,
            'reason' => 'test',
        ]);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_refunds(): void
    {
        $response = $this->getJson('/api/v1/refunds');
        $response->assertStatus(401);
    }
}
