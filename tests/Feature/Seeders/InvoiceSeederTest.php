<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\Invoice\Models\Invoice;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\InvoiceSeeder;
use Database\Seeders\OrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_invoices(): void
    {
        $this->seed([
            \Database\Seeders\ProductCategorySeeder::class,
            \Database\Seeders\ProductSeeder::class,
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\OrderSeeder::class,
            \Database\Seeders\InvoiceSeeder::class,
        ]);

        $this->assertDatabaseHas('invoices', ['invoice_no' => 'INV20260101001', 'status' => 1, 'type' => 0]);
        $this->assertDatabaseHas('invoices', ['invoice_no' => 'INV20260101002', 'status' => 4, 'type' => 1]);
        $this->assertDatabaseHas('invoices', ['invoice_no' => 'INV20260101003', 'status' => 1, 'type' => 2]);
        $this->assertCount(3, Invoice::all());
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed([
            \Database\Seeders\ProductCategorySeeder::class,
            \Database\Seeders\ProductSeeder::class,
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\OrderSeeder::class,
            \Database\Seeders\InvoiceSeeder::class,
        ]);
        $this->seed(\Database\Seeders\InvoiceSeeder::class);

        $this->assertCount(3, Invoice::all());
    }
}
