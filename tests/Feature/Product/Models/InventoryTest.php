<?php

declare(strict_types=1);

namespace Tests\Feature\Product\Models;

use App\Domains\Product\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_inventory(): void
    {
        $inventory = Inventory::factory()->create();

        $this->assertDatabaseHas('inventories', ['id' => $inventory->id]);
    }

    public function test_belongs_to_product(): void
    {
        $inventory = Inventory::factory()->create();

        $this->assertNotNull($inventory->product);
    }

    public function test_qty_fields_are_integers(): void
    {
        $inventory = Inventory::factory()->create([
            'available_qty' => 100,
            'reserved_qty' => 20,
            'locked_qty' => 10,
            'safety_stock' => 50,
            'version' => 5,
        ]);

        $this->assertSame(100, $inventory->available_qty);
        $this->assertSame(20, $inventory->reserved_qty);
        $this->assertSame(10, $inventory->locked_qty);
        $this->assertSame(50, $inventory->safety_stock);
        $this->assertSame(5, $inventory->version);
    }

    public function test_product_id_is_unique(): void
    {
        $inventory = Inventory::factory()->create();

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Inventory::factory()->create(['product_id' => $inventory->product_id]);
    }
}
