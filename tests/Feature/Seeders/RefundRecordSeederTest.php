<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\Payment\Models\RefundRecord;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\RefundRecordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundRecordSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_refund_records(): void
    {
        $this->seed([
            \Database\Seeders\ProductCategorySeeder::class,
            \Database\Seeders\ProductSeeder::class,
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\OrderSeeder::class,
            \Database\Seeders\RefundRecordSeeder::class,
        ]);

        $this->assertDatabaseHas('refund_records', ['refund_no' => 'R20260101001', 'status' => 0]);
        $this->assertDatabaseHas('refund_records', ['refund_no' => 'R20260101002', 'status' => 4]);
        $this->assertDatabaseHas('refund_records', ['refund_no' => 'R20260101003', 'status' => 1]);
        $this->assertCount(3, RefundRecord::all());
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed([
            \Database\Seeders\ProductCategorySeeder::class,
            \Database\Seeders\ProductSeeder::class,
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\OrderSeeder::class,
            \Database\Seeders\RefundRecordSeeder::class,
        ]);
        $this->seed(\Database\Seeders\RefundRecordSeeder::class);

        $this->assertCount(3, RefundRecord::all());
    }
}
