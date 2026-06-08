<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Logistics\Models\Carrier;
use Illuminate\Database\Seeder;

class CarrierSeeder extends Seeder
{
    public function run(): void
    {
        $carriers = [
            [
                'name' => '顺丰速运',
                'code' => 'SF',
                'api_type' => 'sf',
                'config' => ['customer_code' => '', 'check_word' => ''],
                'is_default' => 1,
                'status' => 1,
            ],
            [
                'name' => '中通快递',
                'code' => 'ZTO',
                'api_type' => 'zto',
                'config' => ['customer_id' => '', 'secret' => ''],
                'is_default' => 0,
                'status' => 1,
            ],
            [
                'name' => '韵达快递',
                'code' => 'YTO',
                'api_type' => 'yto',
                'config' => ['client_id' => '', 'secret' => ''],
                'is_default' => 0,
                'status' => 1,
            ],
        ];

        foreach ($carriers as $data) {
            Carrier::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
