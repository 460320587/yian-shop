<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderFile;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFileDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer, 'sanctum');
        return $customer;
    }

    public function test_user_can_delete_own_order_file(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $file = OrderFile::factory()->create(['order_id' => $order->id, 'status' => 1]);

        $response = $this->deleteJson("/api/v1/orders/{$order->id}/files/{$file->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('order_files', [
            'id' => $file->id,
            'status' => 0,
        ]);
    }

    public function test_user_cannot_delete_others_order_file(): void
    {
        $this->authCustomer();
        $otherOrder = Order::factory()->create();
        $file = OrderFile::factory()->create(['order_id' => $otherOrder->id, 'status' => 1]);

        $response = $this->deleteJson("/api/v1/orders/{$otherOrder->id}/files/{$file->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('order_files', [
            'id' => $file->id,
            'status' => 1,
        ]);
    }

    public function test_user_cannot_delete_nonexistent_file(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $response = $this->deleteJson("/api/v1/orders/{$order->id}/files/99999");

        $response->assertStatus(404);
    }

    public function test_user_cannot_delete_file_from_other_order(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $otherOrder = Order::factory()->create(['customer_id' => $customer->id]);
        $file = OrderFile::factory()->create(['order_id' => $otherOrder->id, 'status' => 1]);

        // 尝试用 order A 的 URL 删除 order B 的文件
        $response = $this->deleteJson("/api/v1/orders/{$order->id}/files/{$file->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('order_files', [
            'id' => $file->id,
            'status' => 1,
        ]);
    }

    public function test_guest_cannot_delete_order_file(): void
    {
        $order = Order::factory()->create();
        $file = OrderFile::factory()->create(['order_id' => $order->id, 'status' => 1]);

        $this->deleteJson("/api/v1/orders/{$order->id}/files/{$file->id}")
            ->assertUnauthorized();
    }
}
