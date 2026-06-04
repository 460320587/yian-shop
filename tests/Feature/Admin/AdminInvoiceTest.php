<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Invoice\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_invoices(): void
    {
        $this->authAdmin();
        Invoice::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/invoices');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'invoice_no', 'customer_id', 'status', 'type', 'amount', 'customer', 'order'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_filter_invoices_by_status(): void
    {
        $this->authAdmin();
        Invoice::factory()->create(['status' => 1]);
        Invoice::factory()->create(['status' => 2]);

        $response = $this->getJson('/api/v1/admin/invoices?status=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(1, $response->json('data.0.status'));
    }

    public function test_admin_can_view_invoice_detail(): void
    {
        $this->authAdmin();
        $invoice = Invoice::factory()->create();

        $response = $this->getJson("/api/v1/admin/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonPath('data.title', $invoice->title)
            ->assertJsonStructure([
                'data' => ['id', 'invoice_no', 'customer', 'order'],
            ]);
    }

    public function test_admin_can_audit_approve_invoice(): void
    {
        $this->authAdmin();
        $invoice = Invoice::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/invoices/{$invoice->id}/audit", [
            'status' => 2,
            'remark' => '审核通过',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 2);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 2,
            'remark' => '审核通过',
        ]);
    }

    public function test_admin_can_audit_reject_invoice(): void
    {
        $this->authAdmin();
        $invoice = Invoice::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/invoices/{$invoice->id}/audit", [
            'status' => 3,
            'remark' => '信息不完整',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 3);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 3,
            'remark' => '信息不完整',
        ]);
    }

    public function test_admin_can_issue_invoice(): void
    {
        $this->authAdmin();
        $invoice = Invoice::factory()->create(['status' => 2]);

        $response = $this->putJson("/api/v1/admin/invoices/{$invoice->id}/issue", [
            'invoice_no' => 'INV20260010001',
            'express_no' => 'SF1234567890',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 4)
            ->assertJsonPath('data.invoice_no', 'INV20260010001')
            ->assertJsonPath('data.express_no', 'SF1234567890');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 4,
            'invoice_no' => 'INV20260010001',
            'express_no' => 'SF1234567890',
        ]);
    }

    public function test_admin_cannot_view_nonexistent_invoice(): void
    {
        $this->authAdmin();

        $response = $this->getJson('/api/v1/admin/invoices/99999');

        $response->assertNotFound()
            ->assertJsonPath('code', 404);
    }

    public function test_unauthenticated_cannot_access_invoices(): void
    {
        $this->getJson('/api/v1/admin/invoices')
            ->assertUnauthorized();
    }
}
