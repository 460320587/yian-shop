<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\System\Models\SystemConfig;
use Illuminate\Database\Seeder;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            ['config_key' => 'site_name', 'config_value' => '怡安印刷商城', 'type' => 'string', 'description' => '站点名称', 'group' => 'basic'],
            ['config_key' => 'site_logo', 'config_value' => '/images/logo.png', 'type' => 'string', 'description' => '站点Logo', 'group' => 'basic'],
            ['config_key' => 'contact_phone', 'config_value' => '400-888-8888', 'type' => 'string', 'description' => '客服电话', 'group' => 'basic'],
            ['config_key' => 'contact_email', 'config_value' => 'service@yian.com', 'type' => 'string', 'description' => '客服邮箱', 'group' => 'basic'],
            ['config_key' => 'icp备案号', 'config_value' => '京ICP备12345678号', 'type' => 'string', 'description' => 'ICP备案号', 'group' => 'basic'],
            ['config_key' => 'maintenance_mode', 'config_value' => '0', 'type' => 'bool', 'description' => '维护模式', 'group' => 'system'],
        ];

        foreach ($configs as $data) {
            SystemConfig::updateOrCreate(['config_key' => $data['config_key']], $data);
        }
    }
}
