<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\User\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'phone' => '13800138000',
                'password' => Hash::make('password'),
                'nickname' => '张三',
                'type' => 1,
                'auth_status' => 2,
                'vip_level' => 3,
                'grow_value' => 1500,
                'points' => 500,
                'balance' => 50000, // 500元
                'status' => 1,
                'link_person' => '张三',
            ],
            [
                'phone' => '13800138001',
                'password' => Hash::make('password'),
                'nickname' => '李四',
                'type' => 2,
                'auth_status' => 0,
                'vip_level' => 5,
                'grow_value' => 60000,
                'points' => 2000,
                'balance' => 200000, // 2000元
                'status' => 1,
                'link_person' => '李四',
            ],
            [
                'phone' => '13800138002',
                'password' => Hash::make('password'),
                'nickname' => '王五',
                'type' => 1,
                'auth_status' => 1,
                'vip_level' => 0,
                'grow_value' => 0,
                'points' => 0,
                'balance' => 0,
                'status' => 1,
            ],
            [
                'phone' => '13800138003',
                'password' => Hash::make('password'),
                'nickname' => '赵六',
                'type' => 2,
                'auth_status' => 3,
                'vip_level' => 1,
                'grow_value' => 100,
                'points' => 100,
                'balance' => 10000, // 100元
                'status' => 0, // 禁用
            ],
            [
                'phone' => '13800138004',
                'password' => Hash::make('password'),
                'nickname' => '钱七',
                'type' => 1,
                'auth_status' => 2,
                'vip_level' => 8,
                'grow_value' => 600000,
                'points' => 10000,
                'balance' => 1000000, // 10000元
                'status' => 1,
            ],
        ];

        foreach ($customers as $data) {
            Customer::updateOrCreate(['phone' => $data['phone']], $data);
        }
    }
}
