<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductCategorySeeder::class,
            ProductSeeder::class,
            VipLevelSeeder::class,
            AdminRoleSeeder::class,
            AdminSeeder::class,
            SystemConfigSeeder::class,
            ArticleSeeder::class,
            CustomerSeeder::class,
            CustomerAddressSeeder::class,
            CouponSeeder::class,
            CarrierSeeder::class,
            OrderSeeder::class,
            AfterSaleSeeder::class,
            RefundRecordSeeder::class,
            InvoiceSeeder::class,
            ProductionScheduleSeeder::class,
        ]);
    }
}
