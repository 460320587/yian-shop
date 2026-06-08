<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Enterprise\Enums\AuthStatus;
use App\Domains\Enterprise\Models\EnterpriseAuth;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEnterpriseAuthTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_enterprise_auths(): void
    {
        $this->authAdmin();
        EnterpriseAuth::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/enterprise-auths');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'customer_id', 'company_name', 'credit_code', 'auth_status', 'audit_remark'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_filter_by_auth_status(): void
    {
        $this->authAdmin();
        EnterpriseAuth::factory()->create(['auth_status' => 1]);
        EnterpriseAuth::factory()->create(['auth_status' => 2]);
        EnterpriseAuth::factory()->create(['auth_status' => 3]);

        $response = $this->getJson('/api/v1/admin/enterprise-auths?auth_status=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(1, $response->json('data.0.auth_status'));
    }

    public function test_admin_can_view_enterprise_auth_detail(): void
    {
        $this->authAdmin();
        $auth = EnterpriseAuth::factory()->create();

        $response = $this->getJson("/api/v1/admin/enterprise-auths/{$auth->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $auth->id)
            ->assertJsonPath('data.company_name', $auth->company_name)
            ->assertJsonStructure([
                'data' => [
                    'id', 'customer_id', 'company_name', 'credit_code',
                    'legal_person', 'legal_person_id_card', 'business_license_img',
                    'contact_name', 'contact_phone', 'register_address',
                    'office_address', 'valid_date', 'auth_status', 'audit_remark',
                ],
            ]);
    }

    public function test_admin_can_approve_enterprise_auth(): void
    {
        $this->authAdmin();
        $auth = EnterpriseAuth::factory()->create(['auth_status' => 1]);

        $response = $this->putJson("/api/v1/admin/enterprise-auths/{$auth->id}/audit", [
            'auth_status' => 2,
            'audit_remark' => '审核通过',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.auth_status', 2);

        $this->assertDatabaseHas('enterprise_auths', [
            'id' => $auth->id,
            'auth_status' => 2,
            'audit_remark' => '审核通过',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $auth->customer_id,
            'auth_status' => 2,
        ]);
    }

    public function test_admin_can_reject_enterprise_auth(): void
    {
        $this->authAdmin();
        $auth = EnterpriseAuth::factory()->create(['auth_status' => 1]);

        $response = $this->putJson("/api/v1/admin/enterprise-auths/{$auth->id}/audit", [
            'auth_status' => 3,
            'audit_remark' => '资料不完整，请补充营业执照',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.auth_status', 3);

        $this->assertDatabaseHas('enterprise_auths', [
            'id' => $auth->id,
            'auth_status' => 3,
            'audit_remark' => '资料不完整，请补充营业执照',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $auth->customer_id,
            'auth_status' => 3,
        ]);
    }

    public function test_audit_rejects_invalid_status_transition(): void
    {
        $this->authAdmin();
        $auth = EnterpriseAuth::factory()->create(['auth_status' => 2]);

        $response = $this->putJson("/api/v1/admin/enterprise-auths/{$auth->id}/audit", [
            'auth_status' => 3,
            'audit_remark' => '驳回',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_audit_requires_remark_when_rejected(): void
    {
        $this->authAdmin();
        $auth = EnterpriseAuth::factory()->create(['auth_status' => 1]);

        $response = $this->putJson("/api/v1/admin/enterprise-auths/{$auth->id}/audit", [
            'auth_status' => 3,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_auth_status_enum_has_not_approved_label(): void
    {
        $this->assertEquals('审核不通过', AuthStatus::NotApproved->label());
        $this->assertEquals(3, AuthStatus::NotApproved->value);
    }

    public function test_unauthenticated_cannot_access_enterprise_auths(): void
    {
        $this->getJson('/api/v1/admin/enterprise-auths')->assertUnauthorized();
    }
}
