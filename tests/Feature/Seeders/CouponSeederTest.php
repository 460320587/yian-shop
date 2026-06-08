<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\Coupon\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_seeder_creates_coupons(): void
    {
        $this->seed(\Database\Seeders\CouponSeeder::class);

        $this->assertDatabaseCount('coupons', 4);
        $this->assertDatabaseHas('coupons', ['code' => 'WELCOME100', 'type' => 1]);
        $this->assertDatabaseHas('coupons', ['code' => 'SUMMER20', 'type' => 2]);
    }

    public function test_coupon_seeder_has_active_coupons(): void
    {
        $this->seed(\Database\Seeders\CouponSeeder::class);

        $active = Coupon::available()->count();
        $this->assertGreaterThanOrEqual(2, $active);
    }

    public function test_coupon_seeder_has_unlimited_coupon(): void
    {
        $this->seed(\Database\Seeders\CouponSeeder::class);

        $this->assertDatabaseHas('coupons', ['code' => 'VIP50', 'total_count' => -1]);
    }
}
