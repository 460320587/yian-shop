<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\AfterSale\Models\AfterSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAfterSaleTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_after_sales(): void
    {
        $this->authAdmin();
        AfterSale::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/after-sales');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'after_sale_no', 'customer_id', 'status', 'type', 'customer', 'items'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_filter_after_sales_by_status(): void
    {
        $this->authAdmin();
        AfterSale::factory()->create(['status' => 1]);
        AfterSale::factory()->create(['status' => 2]);

        $response = $this->getJson('/api/v1/admin/after-sales?status=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(1, $response->json('data.0.status'));
    }

    public function test_admin_can_view_after_sale_detail(): void
    {
        $this->authAdmin();
        $afterSale = AfterSale::factory()->create();

        $response = $this->getJson("/api/v1/admin/after-sales/{$afterSale->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $afterSale->id)
            ->assertJsonPath('data.after_sale_no', $afterSale->after_sale_no)
            ->assertJsonStructure([
                'data' => ['id', 'after_sale_no', 'customer', 'items'],
            ]);
    }

    public function test_admin_can_audit_approve_after_sale(): void
    {
        $this->authAdmin();
        $afterSale = AfterSale::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/after-sales/{$afterSale->id}/audit", [
            'status' => 2,
            'approved_amount' => 99.99,
            'audit_remark' => '审核通过，同意退款',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 2);

        $this->assertDatabaseHas('after_sales', [
            'id' => $afterSale->id,
            'status' => 2,
            'approved_amount' => 9999,
            'audit_remark' => '审核通过，同意退款',
        ]);
    }

    public function test_admin_can_audit_reject_after_sale(): void
    {
        $this->authAdmin();
        $afterSale = AfterSale::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/after-sales/{$afterSale->id}/audit", [
            'status' => 3,
            'audit_remark' => '不符合退款条件',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 3);

        $this->assertDatabaseHas('after_sales', [
            'id' => $afterSale->id,
            'status' => 3,
            'audit_remark' => '不符合退款条件',
        ]);
    }

    public function test_admin_cannot_view_nonexistent_after_sale(): void
    {
        $this->authAdmin();

        $response = $this->getJson('/api/v1/admin/after-sales/99999');

        $response->assertNotFound()
            ->assertJsonPath('code', 404);
    }

    public function test_unauthenticated_cannot_access_after_sales(): void
    {
        $this->getJson('/api/v1/admin/after-sales')
            ->assertUnauthorized();
    }
}
