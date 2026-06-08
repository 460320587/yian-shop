<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Admin\Models\Admin;
use App\Domains\Admin\Models\AdminRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $superRole = AdminRole::where('code', 'super')->first();

        Admin::updateOrCreate(
            ['username' => 'admin'],
            [
                'password' => Hash::make('admin123'),
                'real_name' => '超级管理员',
                'phone' => '13800138000',
                'email' => 'admin@yian.com',
                'role' => 'super_admin',
                'role_id' => $superRole?->id,
                'status' => 1,
            ]
        );
    }
}
