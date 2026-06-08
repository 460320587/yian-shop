<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProductCategorySeeder::class,
            ProductSeeder::class,
            VipLevelSeeder::class,
            AdminRoleSeeder::class,
            AdminSeeder::class,
            ArticleSeeder::class,
            SystemConfigSeeder::class,
            CustomerSeeder::class,
            CouponSeeder::class,
            CarrierSeeder::class,
        ]);
    }
}
