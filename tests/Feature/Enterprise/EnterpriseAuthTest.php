<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use App\Domains\Enterprise\Models\EnterpriseAuth;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseAuthTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(array $attributes = []): Customer
    {
        $customer = Customer::factory()->create($attributes);
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_get_auth_status(): void
    {
        $this->authCustomer(['auth_status' => 0]);

        $response = $this->getJson('/api/v1/enterprise/auth-status');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.auth_status', 0)
            ->assertJsonPath('data.auth_status_name', '未提交');
    }

    public function test_user_can_submit_auth_application(): void
    {
        $customer = $this->authCustomer(['auth_status' => 0]);

        $response = $this->postJson('/api/v1/enterprise/apply', [
            'company_name' => '北京怡安印刷科技有限公司',
            'credit_code' => '91110108MA0012345',
            'legal_person' => '张三',
            'legal_person_id_card' => '110101199001011234',
            'business_license_img' => 'https://example.com/license.jpg',
            'contact_name' => '李四',
            'contact_phone' => '13800138000',
            'register_address' => '北京市海淀区',
            'office_address' => '北京市朝阳区',
            'valid_date' => '2026-12-31',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.auth_status', 1);

        $this->assertDatabaseHas('enterprise_auths', [
            'customer_id' => $customer->id,
            'company_name' => '北京怡安印刷科技有限公司',
            'auth_status' => 1,
        ]);

        $customer->refresh();
        $this->assertEquals(1, $customer->auth_status);
    }

    public function test_user_can_re_submit_auth_application(): void
    {
        $customer = $this->authCustomer(['auth_status' => 3]);
        EnterpriseAuth::factory()->create([
            'customer_id' => $customer->id,
            'auth_status' => 3,
            'company_name' => '旧名称',
        ]);

        $response = $this->postJson('/api/v1/enterprise/apply', [
            'company_name' => '新公司名称',
            'credit_code' => '91110108MA0012345',
            'legal_person' => '张三',
            'legal_person_id_card' => '110101199001011234',
            'business_license_img' => 'https://example.com/license.jpg',
            'contact_name' => '李四',
            'contact_phone' => '13800138000',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('enterprise_auths', [
            'customer_id' => $customer->id,
            'company_name' => '新公司名称',
            'auth_status' => 1,
        ]);
    }

    public function test_user_can_get_auth_info(): void
    {
        $customer = $this->authCustomer(['auth_status' => 2]);
        EnterpriseAuth::factory()->create([
            'customer_id' => $customer->id,
            'auth_status' => 2,
        ]);

        $response = $this->getJson('/api/v1/enterprise/info');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.auth_status', 2);
    }

    public function test_auth_info_returns_404_when_not_submitted(): void
    {
        $this->authCustomer(['auth_status' => 0]);

        $response = $this->getJson('/api/v1/enterprise/info');

        $response->assertStatus(404);
    }

    public function test_apply_requires_required_fields(): void
    {
        $this->authCustomer();

        $response = $this->postJson('/api/v1/enterprise/apply', [
            'company_name' => '',
        ]);

        $response->assertStatus(422);
    }
}
