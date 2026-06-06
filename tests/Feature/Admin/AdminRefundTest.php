<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerWallet;
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
        $refund = RefundRecord::factory()->create([
            'status' => 0,
            'refund_path' => 'original',
        ]);

        $response = $this->putJson("/api/v1/admin/refunds/{$refund->id}/audit", [
            'action' => 'approve',
            'remark' => '同意退款',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('refund_records', [
            'id' => $refund->id,
            'status' => 4,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_approve_refund_credits_customer_wallet(): void
    {
        $admin = $this->authAdmin();
        $customer = Customer::factory()->create(['balance' => 10000]);
        CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 10000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);
        $refund = RefundRecord::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 5000,
            'status' => 0,
            'refund_path' => 'wallet',
        ]);

        $response = $this->putJson("/api/v1/admin/refunds/{$refund->id}/audit", [
            'action' => 'approve',
            'remark' => '同意退款',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('refund_records', [
            'id' => $refund->id,
            'status' => 4,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'customer_id' => $customer->id,
            'type' => 3, // refund
            'amount' => 5000,
            'payment_no' => $refund->refund_no,
        ]);
        $customer->refresh();
        $this->assertEquals(15000, $customer->wallet->fresh()->balance->amount);
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
