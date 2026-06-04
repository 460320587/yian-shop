<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Coupon\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->withHeader('Authorization', 'Bearer ' . $admin->createToken('admin')->plainTextToken);
        return $admin;
    }

    public function test_admin_can_list_coupons(): void
    {
        $this->authAdmin();
        Coupon::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/coupons');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_coupon(): void
    {
        $this->authAdmin();

        $response = $this->postJson('/api/v1/admin/coupons', [
            'code' => 'NEWCOUPON',
            'name' => '新用户券',
            'type' => 1,
            'value' => 500,
            'min_amount' => 1000,
            'max_discount' => 0,
            'start_at' => now()->subDay()->toDateTimeString(),
            'end_at' => now()->addDays(7)->toDateTimeString(),
            'total_count' => 100,
            'per_customer_limit' => 1,
            'status' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.code', 'NEWCOUPON');

        $this->assertDatabaseHas('coupons', [
            'code' => 'NEWCOUPON',
            'name' => '新用户券',
            'type' => 1,
            'value' => 500,
        ]);
    }

    public function test_admin_can_update_coupon(): void
    {
        $this->authAdmin();
        $coupon = Coupon::factory()->create(['name' => '旧名称']);

        $response = $this->putJson("/api/v1/admin/coupons/{$coupon->id}", [
            'name' => '新名称',
            'value' => 800,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', '新名称')
            ->assertJsonPath('data.value', 800);
    }

    public function test_admin_can_toggle_coupon_status(): void
    {
        $this->authAdmin();
        $coupon = Coupon::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/coupons/{$coupon->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'status' => 2,
        ]);
    }
}
