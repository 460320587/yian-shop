<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRefundTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_refunds(): void
    {
        $this->authAdmin();
        RefundRecord::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/refunds');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'refund_no', 'order_id', 'customer_id', 'amount', 'reason', 'status'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_filter_refunds_by_status(): void
    {
        $this->authAdmin();
        RefundRecord::factory()->create(['status' => 0]);
        RefundRecord::factory()->create(['status' => 1]);
        RefundRecord::factory()->create(['status' => 2]);

        $response = $this->getJson('/api/v1/admin/refunds?status=0');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(0, $response->json('data.0.status'));
    }

    public function test_admin_can_view_refund_detail(): void
    {
        $this->authAdmin();
        $refund = RefundRecord::factory()->create();

        $response = $this->getJson("/api/v1/admin/refunds/{$refund->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $refund->id)
            ->assertJsonPath('data.refund_no', $refund->refund_no)
            ->assertJsonStructure([
                'data' => ['id', 'refund_no', 'order_id', 'customer_id', 'amount', 'reason', 'status', 'created_at'],
            ]);
    }

    public function test_admin_can_approve_refund(): void
    {
        $admin = $this->authAdmin();
        $refund = RefundRecord::factory()->create(['status' => 0]);

        $response = $this->putJson("/api/v1/admin/refunds/{$refund->id}/audit", [
            'action' => 'approve',
            'remark' => '同意退款',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('refund_records', [
            'id' => $refund->id,
            'status' => 1,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reject_refund(): void
    {
        $admin = $this->authAdmin();
        $refund = RefundRecord::factory()->create(['status' => 0]);

        $response = $this->putJson("/api/v1/admin/refunds/{$refund->id}/audit", [
            'action' => 'reject',
            'remark' => '不符合退款条件',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('refund_records', [
            'id' => $refund->id,
            'status' => 2,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_cannot_audit_non_pending_refund(): void
    {
        $this->authAdmin();
        $refund = RefundRecord::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/refunds/{$refund->id}/audit", [
            'action' => 'approve',
            'remark' => '同意',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_audit_requires_remark(): void
    {
        $this->authAdmin();
        $refund = RefundRecord::factory()->create(['status' => 0]);

        $response = $this->putJson("/api/v1/admin/refunds/{$refund->id}/audit", [
            'action' => 'approve',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_unauthenticated_cannot_access_refunds(): void
    {
        $this->getJson('/api/v1/admin/refunds')->assertUnauthorized();
    }
}
