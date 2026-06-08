<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerAddress;
use Illuminate\Database\Seeder;

class CustomerAddressSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            return;
        }

        $addresses = [
            [
                'customer_id' => $customers[0]->id,
                'province_name' => '河南省',
                'city_name' => '郑州市',
                'county_name' => '金水区',
                'detail_address' => '花园路 100 号怡安大厦 8 楼',
                'zip_code' => '450000',
                'contact_name' => '张三',
                'contact_phone' => '13800138000',
                'is_default' => true,
                'tag' => '公司',
            ],
            [
                'customer_id' => $customers[0]->id,
                'province_name' => '河南省',
                'city_name' => '郑州市',
                'county_name' => '二七区',
                'detail_address' => '建设路 50 号',
                'zip_code' => '450000',
                'contact_name' => '张三',
                'contact_phone' => '13800138000',
                'is_default' => false,
                'tag' => '家',
            ],
            [
                'customer_id' => $customers[1]->id,
                'province_name' => '广东省',
                'city_name' => '深圳市',
                'county_name' => '南山区',
                'detail_address' => '科技园南路 88 号',
                'zip_code' => '518000',
                'contact_name' => '李四',
                'contact_phone' => '13800138001',
                'is_default' => true,
                'tag' => '公司',
            ],
            [
                'customer_id' => $customers[2]->id,
                'province_name' => '浙江省',
                'city_name' => '杭州市',
                'county_name' => '西湖区',
                'detail_address' => '文三路 200 号',
                'zip_code' => '310000',
                'contact_name' => '王五',
                'contact_phone' => '13800138002',
                'is_default' => true,
                'tag' => '公司',
            ],
            [
                'customer_id' => $customers[4]->id,
                'province_name' => '北京市',
                'city_name' => '北京市',
                'county_name' => '朝阳区',
                'detail_address' => '建国路 88 号 SOHO 现代城',
                'zip_code' => '100000',
                'contact_name' => '钱七',
                'contact_phone' => '13800138004',
                'is_default' => true,
                'tag' => '公司',
            ],
        ];

        foreach ($addresses as $data) {
            CustomerAddress::updateOrCreate(
                [
                    'customer_id' => $data['customer_id'],
                    'detail_address' => $data['detail_address'],
                ],
                $data
            );
        }
    }
}
