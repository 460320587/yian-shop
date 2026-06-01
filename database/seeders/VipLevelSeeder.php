<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Vip\Models\VipLevel;
use Illuminate\Database\Seeder;

class VipLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['level' => 0, 'name' => '普通会员', 'min_points' => 0,        'discount' => 1.00, 'icon' => null, 'privileges' => []],
            ['level' => 1, 'name' => '银牌会员', 'min_points' => 0,        'discount' => 1.00, 'icon' => null, 'privileges' => ['sample_discount' => 0.90]],
            ['level' => 2, 'name' => '银牌会员Ⅱ', 'min_points' => 100,     'discount' => 1.00, 'icon' => null, 'privileges' => ['sample_discount' => 0.90]],
            ['level' => 3, 'name' => '银牌会员Ⅲ', 'min_points' => 1000,    'discount' => 1.00, 'icon' => null, 'privileges' => ['sample_discount' => 0.90]],
            ['level' => 4, 'name' => '金牌会员',   'min_points' => 20000,   'discount' => 1.00, 'icon' => null, 'privileges' => []],
            ['level' => 5, 'name' => '钻石会员',   'min_points' => 50000,   'discount' => 1.00, 'icon' => null, 'privileges' => ['sample_discount' => 0.50]],
            ['level' => 6, 'name' => '皇冠会员',   'min_points' => 100000,  'discount' => 0.93, 'icon' => null, 'privileges' => ['deadline_extension' => 30]],
            ['level' => 7, 'name' => '皇冠会员Ⅱ', 'min_points' => 200000,  'discount' => 0.92, 'icon' => null, 'privileges' => ['deadline_extension' => 30]],
            ['level' => 8, 'name' => '至尊会员',   'min_points' => 500000,  'discount' => 0.90, 'icon' => null, 'privileges' => ['deadline_extension' => 60]],
        ];

        foreach ($levels as $data) {
            VipLevel::updateOrCreate(['level' => $data['level']], $data);
        }
    }
}
