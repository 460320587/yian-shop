<?php

declare(strict_types=1);

namespace Tests\Feature\Invoice;

use App\Domains\Invoice\Models\Invoice;
use App\Domains\Invoice\Models\InvoiceTitle;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    // ========== 发票抬头 ==========

    public function test_user_can_get_invoice_title_list(): void
    {
        $customer = $this->authCustomer();
        InvoiceTitle::factory()->count(3)->create(['customer_id' => $customer->id]);
        InvoiceTitle::factory()->create();

        $response = $this->getJson('/api/v1/invoice-titles');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_invoice_title(): void
    {
        $customer = $this->authCustomer();

        $response = $this->postJson('/api/v1/invoice-titles', [
            'title_type' => 1,
            'invoice_category' => 2,
            'company_name' => '测试公司',
            'tax_number' => '91320000MA1P5TEST',
            'bank_name' => '工商银行',
            'bank_account' => '1234567890',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.company_name', '测试公司');

        $this->assertDatabaseHas('invoice_titles', [
            'customer_id' => $customer->id,
            'company_name' => '测试公司',
        ]);
    }

    public function test_create_title_sets_default_and_clears_others(): void
    {
        $customer = $this->authCustomer();
        InvoiceTitle::factory()->create(['customer_id' => $customer->id, 'is_default' => 1]);

        $response = $this->postJson('/api/v1/invoice-titles', [
            'title_type' => 1,
            'invoice_category' => 1,
            'company_name' => '新公司',
            'is_default' => 1,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invoice_titles', ['company_name' => '新公司', 'is_default' => 1]);
        $this->assertDatabaseMissing('invoice_titles', ['customer_id' => $customer->id, 'is_default' => 1, 'company_name' => '!=新公司']);
    }

    public function test_user_can_update_invoice_title(): void
    {
        $customer = $this->authCustomer();
        $title = InvoiceTitle::factory()->create(['customer_id' => $customer->id]);

        $response = $this->putJson("/api/v1/invoice-titles/{$title->id}", [
            'company_name' => '更新后的公司',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.company_name', '更新后的公司');
    }

    public function test_user_cannot_update_others_title(): void
    {
        $this->authCustomer();
        $otherTitle = InvoiceTitle::factory()->create();

        $response = $this->putJson("/api/v1/invoice-titles/{$otherTitle->id}", [
            'company_name' => 'hack',
        ]);

        $response->assertStatus(404);
    }

    public function test_user_can_delete_invoice_title(): void
    {
        $customer = $this->authCustomer();
        $title = InvoiceTitle::factory()->create(['customer_id' => $customer->id]);

        $response = $this->deleteJson("/api/v1/invoice-titles/{$title->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('invoice_titles', ['id' => $title->id]);
    }

    // ========== 发票申请 ==========

    public function test_user_can_get_invoice_list(): void
    {
        $customer = $this->authCustomer();
        Invoice::factory()->count(3)->create(['customer_id' => $customer->id]);
        Invoice::factory()->create();

        $response = $this->getJson('/api/v1/invoices');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data');
    }

    public function test_list_can_filter_by_status(): void
    {
        $customer = $this->authCustomer();
        Invoice::factory()->count(2)->create(['customer_id' => $customer->id, 'status' => 1]);
        Invoice::factory()->count(3)->create(['customer_id' => $customer->id, 'status' => 4]);

        $response = $this->getJson('/api/v1/invoices?status=4');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_invoice(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $title = InvoiceTitle::factory()->create(['customer_id' => $customer->id]);

        $response = $this->postJson('/api/v1/invoices', [
            'order_id' => $order->id,
            'title_id' => $title->id,
            'type' => 1,
            'email' => 'finance@test.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.title', $title->company_name);

        $this->assertDatabaseHas('invoices', [
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 1,
        ]);
    }

    public function test_create_invoice_requires_order_ownership(): void
    {
        $customer = $this->authCustomer();
        $otherCustomer = \App\Domains\User\Models\Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $otherCustomer->id]);
        $title = InvoiceTitle::factory()->create(['customer_id' => $customer->id]);

        $response = $this->postJson('/api/v1/invoices', [
            'order_id' => $order->id,
            'title_id' => $title->id,
            'type' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_create_invoice_rejects_duplicate(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $title = InvoiceTitle::factory()->create(['customer_id' => $customer->id]);
        Invoice::factory()->create(['order_id' => $order->id, 'customer_id' => $customer->id, 'status' => 1]);

        $response = $this->postJson('/api/v1/invoices', [
            'order_id' => $order->id,
            'title_id' => $title->id,
            'type' => 1,
        ]);

        $response->assertStatus(400);
    }

    public function test_user_can_get_invoice_detail(): void
    {
        $customer = $this->authCustomer();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        $response = $this->getJson("/api/v1/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $invoice->id);
    }

    public function test_user_cannot_view_others_invoice(): void
    {
        $this->authCustomer();
        $otherInvoice = Invoice::factory()->create();

        $response = $this->getJson("/api/v1/invoices/{$otherInvoice->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_cancel_pending_invoice(): void
    {
        $customer = $this->authCustomer();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => 1]);

        $response = $this->putJson("/api/v1/invoices/{$invoice->id}/cancel");

        $response->assertStatus(200);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 0]);
    }

    public function test_user_cannot_cancel_issued_invoice(): void
    {
        $customer = $this->authCustomer();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => 4]);

        $response = $this->putJson("/api/v1/invoices/{$invoice->id}/cancel");

        $response->assertStatus(400);
    }
}
