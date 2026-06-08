<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\Order\Models\ProductionSchedule;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\ProductionScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionScheduleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_production_schedules(): void
    {
        $this->seed([
            \Database\Seeders\ProductCategorySeeder::class,
            \Database\Seeders\ProductSeeder::class,
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\OrderSeeder::class,
            \Database\Seeders\ProductionScheduleSeeder::class,
        ]);

        $this->assertDatabaseHas('production_schedules', ['process_name' => '印刷', 'status' => 0]);
        $this->assertDatabaseHas('production_schedules', ['process_name' => '覆膜', 'status' => 2]);
        $this->assertDatabaseHas('production_schedules', ['process_name' => '裁切', 'status' => 3]);
        $this->assertCount(3, ProductionSchedule::all());
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed([
            \Database\Seeders\ProductCategorySeeder::class,
            \Database\Seeders\ProductSeeder::class,
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\OrderSeeder::class,
            \Database\Seeders\ProductionScheduleSeeder::class,
        ]);
        $this->seed(\Database\Seeders\ProductionScheduleSeeder::class);

        $this->assertCount(3, ProductionSchedule::all());
    }
}
