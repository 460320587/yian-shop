<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Database\Seeders\AfterSaleSeeder;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\OrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AfterSaleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_after_sales(): void
    {
        $this->seed([
            \Database\Seeders\ProductCategorySeeder::class,
            \Database\Seeders\ProductSeeder::class,
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\OrderSeeder::class,
            \Database\Seeders\AfterSaleSeeder::class,
        ]);

        $this->assertDatabaseHas('after_sales', ['after_sale_no' => 'AS20260101001', 'status' => 1]);
        $this->assertDatabaseHas('after_sales', ['after_sale_no' => 'AS20260101002', 'status' => 5]);
        $this->assertDatabaseHas('after_sales', ['after_sale_no' => 'AS20260101003', 'status' => 2]);
        $this->assertCount(3, AfterSale::all());
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed([
            \Database\Seeders\ProductCategorySeeder::class,
            \Database\Seeders\ProductSeeder::class,
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\OrderSeeder::class,
            \Database\Seeders\AfterSaleSeeder::class,
        ]);
        $this->seed(\Database\Seeders\AfterSaleSeeder::class);

        $this->assertCount(3, AfterSale::all());
    }
}
