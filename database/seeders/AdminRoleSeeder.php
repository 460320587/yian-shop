<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Admin\Models\AdminPermission;
use App\Domains\Admin\Models\AdminRole;
use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => '超级管理员', 'code' => 'super', 'description' => '全部权限', 'status' => 1],
            ['name' => '运营', 'code' => 'operator', 'description' => '日常运营', 'status' => 1],
            ['name' => '财务', 'code' => 'finance', 'description' => '财务相关', 'status' => 1],
        ];

        foreach ($roles as $data) {
            AdminRole::updateOrCreate(['code' => $data['code']], $data);
        }

        $permissions = [
            ['name' => '用户查看', 'code' => 'user:view', 'group' => '用户管理', 'type' => 1],
            ['name' => '用户编辑', 'code' => 'user:edit', 'group' => '用户管理', 'type' => 2],
            ['name' => '订单查看', 'code' => 'order:view', 'group' => '订单管理', 'type' => 1],
            ['name' => '订单编辑', 'code' => 'order:edit', 'group' => '订单管理', 'type' => 2],
            ['name' => '商品查看', 'code' => 'product:view', 'group' => '商品管理', 'type' => 1],
            ['name' => '商品编辑', 'code' => 'product:edit', 'group' => '商品管理', 'type' => 2],
            ['name' => '内容查看', 'code' => 'content:view', 'group' => '内容管理', 'type' => 1],
            ['name' => '内容编辑', 'code' => 'content:edit', 'group' => '内容管理', 'type' => 2],
            ['name' => '财务查看', 'code' => 'finance:view', 'group' => '财务管理', 'type' => 1],
            ['name' => '财务编辑', 'code' => 'finance:edit', 'group' => '财务管理', 'type' => 2],
            ['name' => '系统查看', 'code' => 'system:view', 'group' => '系统管理', 'type' => 1],
            ['name' => '系统编辑', 'code' => 'system:edit', 'group' => '系统管理', 'type' => 2],
        ];

        foreach ($permissions as $data) {
            AdminPermission::updateOrCreate(['code' => $data['code']], $data);
        }

        $superRole = AdminRole::where('code', 'super')->first();
        if ($superRole) {
            $permIds = AdminPermission::pluck('id')->all();
            $superRole->permissions()->sync($permIds);
        }
    }
}
