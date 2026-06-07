<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderFile;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderFileTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer, 'sanctum');
        return $customer;
    }

    public function test_user_can_list_order_files(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        OrderFile::factory()->count(3)->create(['order_id' => $order->id]);
        OrderFile::factory()->create(); // other order

        $response = $this->getJson("/api/v1/orders/{$order->id}/files");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_cannot_list_others_order_files(): void
    {
        $this->authCustomer();
        $otherOrder = Order::factory()->create();
        OrderFile::factory()->create(['order_id' => $otherOrder->id]);

        $response = $this->getJson("/api/v1/orders/{$otherOrder->id}/files");

        $response->assertStatus(403);
    }

    public function test_user_can_upload_order_file(): void
    {
        Storage::fake('public');
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $file = UploadedFile::fake()->create('design.pdf', 100, 'application/pdf');

        $response = $this->postJson("/api/v1/orders/{$order->id}/files", [
            'file' => $file,
            'file_name' => 'design.pdf',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.file_name', 'design.pdf');

        $this->assertDatabaseHas('order_files', [
            'order_id' => $order->id,
            'file_name' => 'design.pdf',
            'status' => 1,
        ]);

        $this->assertNotEmpty($response->json('data.file_url'));
    }

    public function test_upload_requires_pdf_or_image(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $file = UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload');

        $response = $this->postJson("/api/v1/orders/{$order->id}/files", [
            'file' => $file,
            'file_name' => 'virus.exe',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_upload_rejects_oversized_file(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $file = UploadedFile::fake()->create('huge.pdf', 60000, 'application/pdf');

        $response = $this->postJson("/api/v1/orders/{$order->id}/files", [
            'file' => $file,
            'file_name' => 'huge.pdf',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_guest_cannot_access_order_files(): void
    {
        $order = Order::factory()->create();

        $this->getJson("/api/v1/orders/{$order->id}/files")
            ->assertUnauthorized();
    }
}
