<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Coupon\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME100',
                'name' => '新用户满100减10',
                'description' => '注册新用户专享，满100元可用',
                'type' => 1, // 满减
                'value' => 1000, // 10元
                'min_amount' => 10000, // 100元
                'max_discount' => 1000,
                'start_at' => now()->subDays(7),
                'end_at' => now()->addDays(30),
                'total_count' => 1000,
                'per_customer_limit' => 1,
                'claimed_count' => 0,
                'used_count' => 0,
                'status' => 1,
            ],
            [
                'code' => 'SUMMER20',
                'name' => '夏日8折券',
                'description' => '全品类8折，最高抵扣50元',
                'type' => 2, // 折扣
                'value' => 80, // 8折
                'min_amount' => 5000, // 50元
                'max_discount' => 5000, // 50元
                'start_at' => now()->subDays(3),
                'end_at' => now()->addDays(27),
                'total_count' => 500,
                'per_customer_limit' => 2,
                'claimed_count' => 0,
                'used_count' => 0,
                'status' => 1,
            ],
            [
                'code' => 'VIP50',
                'name' => 'VIP专享50元券',
                'description' => 'VIP会员专属，无门槛使用',
                'type' => 1,
                'value' => 5000, // 50元
                'min_amount' => 0,
                'max_discount' => 5000,
                'start_at' => now()->subDays(1),
                'end_at' => now()->addDays(60),
                'total_count' => -1, // 不限量
                'per_customer_limit' => 1,
                'claimed_count' => 0,
                'used_count' => 0,
                'status' => 1,
            ],
            [
                'code' => 'EXPIRED01',
                'name' => '已过期测试券',
                'description' => '用于测试过期状态',
                'type' => 1,
                'value' => 500,
                'min_amount' => 0,
                'max_discount' => 500,
                'start_at' => now()->subDays(30),
                'end_at' => now()->subDays(1),
                'total_count' => 100,
                'per_customer_limit' => 1,
                'claimed_count' => 0,
                'used_count' => 0,
                'status' => 1,
            ],
        ];

        foreach ($coupons as $data) {
            Coupon::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
