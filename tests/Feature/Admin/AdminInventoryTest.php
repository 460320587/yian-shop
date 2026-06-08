<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Product\Models\Inventory;
use App\Domains\Product\Models\InventoryLog;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_inventory(): void
    {
        $this->authAdmin();
        $product = Product::factory()->create(['name' => '铜版纸']);
        Inventory::factory()->create([
            'product_id' => $product->id,
            'available_qty' => 50,
            'safety_stock' => 20,
        ]);

        $response = $this->getJson('/api/v1/admin/inventory');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_name', '铜版纸')
            ->assertJsonPath('data.0.available_qty', 50)
            ->assertJsonPath('data.0.safety_stock', 20);
    }

    public function test_admin_can_adjust_inventory(): void
    {
        $admin = $this->authAdmin();
        $product = Product::factory()->create(['name' => '哑粉纸']);
        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'available_qty' => 100,
            'safety_stock' => 20,
        ]);

        $response = $this->putJson('/api/v1/admin/inventory/' . $inventory->id . '/adjust', [
            'qty_change' => 50,
            'reason' => '补货入库',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'available_qty' => 150,
        ]);

        $this->assertDatabaseHas('inventory_logs', [
            'product_id' => $product->id,
            'type' => 5,
            'qty_before' => 100,
            'qty_change' => 50,
            'qty_after' => 150,
            'reason' => '补货入库',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reduce_inventory(): void
    {
        $admin = $this->authAdmin();
        $product = Product::factory()->create();
        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'available_qty' => 100,
        ]);

        $response = $this->putJson('/api/v1/admin/inventory/' . $inventory->id . '/adjust', [
            'qty_change' => -30,
            'reason' => '盘点损耗',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'available_qty' => 70,
        ]);
    }

    public function test_adjust_inventory_rejects_negative_result(): void
    {
        $this->authAdmin();
        $product = Product::factory()->create();
        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'available_qty' => 10,
        ]);

        $response = $this->putJson('/api/v1/admin/inventory/' . $inventory->id . '/adjust', [
            'qty_change' => -20,
            'reason' => '错误操作',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_admin_can_view_inventory_logs(): void
    {
        $this->authAdmin();
        $product = Product::factory()->create();
        InventoryLog::factory()->count(3)->create(['product_id' => $product->id]);

        $response = $this->getJson('/api/v1/admin/inventory/logs?product_id=' . $product->id);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data');
    }

    public function test_guest_cannot_access_inventory(): void
    {
        $response = $this->getJson('/api/v1/admin/inventory');
        $response->assertStatus(401);
    }

    public function test_adjust_inventory_requires_reason(): void
    {
        $this->authAdmin();
        $inventory = Inventory::factory()->create();

        $response = $this->putJson('/api/v1/admin/inventory/' . $inventory->id . '/adjust', [
            'qty_change' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422)
            ->assertJsonPath('data.reason.0', 'validation.required');
    }
}
