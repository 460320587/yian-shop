<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_customers(): void
    {
        $this->authAdmin();
        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/customers');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'phone', 'nickname', 'status', 'vip_level'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_search_customers_by_phone(): void
    {
        $this->authAdmin();
        Customer::factory()->create(['phone' => '13800138000', 'nickname' => '张三']);
        Customer::factory()->create(['phone' => '13900139000', 'nickname' => '李四']);

        $response = $this->getJson('/api/v1/admin/customers?keyword=13800138000');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('13800138000', $response->json('data.0.phone'));
    }

    public function test_admin_can_search_customers_by_nickname(): void
    {
        $this->authAdmin();
        Customer::factory()->create(['phone' => '13800138000', 'nickname' => '张三']);
        Customer::factory()->create(['phone' => '13900139000', 'nickname' => '李四']);

        $response = $this->getJson('/api/v1/admin/customers?keyword=张三');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('张三', $response->json('data.0.nickname'));
    }

    public function test_admin_can_filter_customers_by_status(): void
    {
        $this->authAdmin();
        Customer::factory()->create(['status' => 1]);
        Customer::factory()->create(['status' => 0]);

        $response = $this->getJson('/api/v1/admin/customers?status=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(1, $response->json('data.0.status'));
    }

    public function test_admin_can_filter_customers_by_vip_level(): void
    {
        $this->authAdmin();
        Customer::factory()->create(['vip_level' => 3]);
        Customer::factory()->create(['vip_level' => 5]);

        $response = $this->getJson('/api/v1/admin/customers?vip_level=3');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(3, $response->json('data.0.vip_level'));
    }

    public function test_admin_can_view_customer_detail(): void
    {
        $this->authAdmin();
        $customer = Customer::factory()->create();

        $response = $this->getJson("/api/v1/admin/customers/{$customer->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.phone', $customer->phone)
            ->assertJsonStructure([
                'data' => ['id', 'phone', 'nickname', 'avatar', 'status', 'vip_level', 'addresses', 'brands'],
            ]);
    }

    public function test_admin_cannot_view_nonexistent_customer(): void
    {
        $this->authAdmin();

        $response = $this->getJson('/api/v1/admin/customers/99999');

        $response->assertNotFound()
            ->assertJsonPath('code', 404);
    }

    public function test_unauthenticated_cannot_access_customers(): void
    {
        $this->getJson('/api/v1/admin/customers')
            ->assertUnauthorized();
    }
}
