<?php

declare(strict_types=1);

namespace Tests\Feature\Coupon;

use App\Domains\Coupon\Models\Coupon;
use App\Domains\Coupon\Models\CustomerCoupon;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    // ========== 可领券列表 ==========

    public function test_user_can_list_available_coupons(): void
    {
        $this->authCustomer();

        // 可领的券
        Coupon::factory()->create([
            'code' => 'SAVE20',
            'name' => '满100减20',
            'status' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'total_count' => 100,
            'claimed_count' => 50,
        ]);

        // 已停用的券（不应出现）
        Coupon::factory()->create([
            'code' => 'DISABLED',
            'name' => '已停用',
            'status' => 2,
        ]);

        // 已过期的券（不应出现）
        Coupon::factory()->create([
            'code' => 'EXPIRED',
            'name' => '已过期',
            'status' => 1,
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDays(5),
        ]);

        // 已领完的券（不应出现）
        Coupon::factory()->create([
            'code' => 'SOLDOUT',
            'name' => '已领完',
            'status' => 1,
            'total_count' => 10,
            'claimed_count' => 10,
        ]);

        $response = $this->getJson('/api/v1/coupons');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'SAVE20');
    }

    // ========== 领券 ==========

    public function test_user_can_claim_coupon(): void
    {
        $customer = $this->authCustomer();

        $coupon = Coupon::factory()->create([
            'code' => 'CLAIMME',
            'status' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'total_count' => 100,
            'claimed_count' => 0,
            'per_customer_limit' => 1,
        ]);

        $response = $this->postJson("/api/v1/coupons/{$coupon->id}/claim");

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 1);

        $this->assertDatabaseHas('customer_coupons', [
            'customer_id' => $customer->id,
            'coupon_id' => $coupon->id,
            'status' => 1,
        ]);

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'claimed_count' => 1,
        ]);
    }

    public function test_claim_coupon_fails_when_exhausted(): void
    {
        $this->authCustomer();

        $coupon = Coupon::factory()->create([
            'code' => 'SOLDOUT',
            'status' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'total_count' => 5,
            'claimed_count' => 5,
        ]);

        $response = $this->postJson("/api/v1/coupons/{$coupon->id}/claim");

        $response->assertStatus(400)
            ->assertJsonPath('code', 6002);
    }

    public function test_claim_coupon_fails_when_over_limit(): void
    {
        $customer = $this->authCustomer();

        $coupon = Coupon::factory()->create([
            'code' => 'LIMIT1',
            'status' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'total_count' => 100,
            'per_customer_limit' => 1,
        ]);

        // 先领一张
        CustomerCoupon::factory()->create([
            'customer_id' => $customer->id,
            'coupon_id' => $coupon->id,
            'status' => 1,
        ]);

        $response = $this->postJson("/api/v1/coupons/{$coupon->id}/claim");

        $response->assertStatus(400)
            ->assertJsonPath('code', 6004);
    }

    public function test_claim_coupon_fails_when_expired(): void
    {
        $this->authCustomer();

        $coupon = Coupon::factory()->create([
            'code' => 'EXPIRED',
            'status' => 1,
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDays(1),
            'total_count' => 100,
        ]);

        $response = $this->postJson("/api/v1/coupons/{$coupon->id}/claim");

        $response->assertStatus(400)
            ->assertJsonPath('code', 6001);
    }

    public function test_claim_coupon_fails_when_not_found(): void
    {
        $this->authCustomer();

        $response = $this->postJson('/api/v1/coupons/99999/claim');

        $response->assertStatus(404)
            ->assertJsonPath('code', 6000);
    }

    // ========== 我的优惠券 ==========

    public function test_user_can_list_my_coupons(): void
    {
        $customer = $this->authCustomer();
        $otherCustomer = Customer::factory()->create();

        $coupon = Coupon::factory()->create(['code' => 'MYCOUPON']);

        // 我的券
        CustomerCoupon::factory()->create([
            'customer_id' => $customer->id,
            'coupon_id' => $coupon->id,
            'status' => 1,
        ]);

        // 别人的券
        CustomerCoupon::factory()->create([
            'customer_id' => $otherCustomer->id,
            'coupon_id' => $coupon->id,
            'status' => 1,
        ]);

        $response = $this->getJson('/api/v1/my-coupons');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.coupon.code', 'MYCOUPON');
    }

    public function test_my_coupons_can_filter_by_status(): void
    {
        $customer = $this->authCustomer();
        $coupon = Coupon::factory()->create();

        CustomerCoupon::factory()->create([
            'customer_id' => $customer->id,
            'coupon_id' => $coupon->id,
            'status' => 1,
        ]);

        CustomerCoupon::factory()->create([
            'customer_id' => $customer->id,
            'coupon_id' => $coupon->id,
            'status' => 2,
        ]);

        $response = $this->getJson('/api/v1/my-coupons?status=2');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 2);
    }

    public function test_expired_coupon_not_shown_in_available_list(): void
    {
        $customer = $this->authCustomer();
        $coupon = Coupon::factory()->create();

        // 创建一个已过期但 status=1 的用户券记录
        CustomerCoupon::factory()->create([
            'customer_id' => $customer->id,
            'coupon_id' => $coupon->id,
            'status' => 1,
            'expired_at' => now()->subDay(),
        ]);

        // 请求未使用(status=1)的券，但已过期的应被过滤
        $response = $this->getJson('/api/v1/my-coupons?status=1');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
