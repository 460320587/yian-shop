<?php

declare(strict_types=1);

namespace Tests\Feature\Product\Models;

use App\Domains\Product\Models\InventoryLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_inventory_log(): void
    {
        $log = InventoryLog::factory()->create();

        $this->assertDatabaseHas('inventory_logs', ['id' => $log->id]);
    }

    public function test_belongs_to_product(): void
    {
        $log = InventoryLog::factory()->create();

        $this->assertNotNull($log->product);
    }

    public function test_qty_fields_are_integers(): void
    {
        $log = InventoryLog::factory()->create([
            'qty_before' => 100,
            'qty_change' => -20,
            'qty_after' => 80,
        ]);

        $this->assertSame(100, $log->qty_before);
        $this->assertSame(-20, $log->qty_change);
        $this->assertSame(80, $log->qty_after);
    }

    public function test_type_is_integer(): void
    {
        $log = InventoryLog::factory()->create(['type' => 3]);

        $this->assertSame(3, $log->type);
    }

    public function test_created_by_can_be_null(): void
    {
        $log = InventoryLog::factory()->create(['created_by' => null]);

        $this->assertNull($log->created_by);
    }
}
