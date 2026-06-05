<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Store\Models;

use App\Domains\Store\Models\Store;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_store(): void
    {
        $store = Store::factory()->create();

        $this->assertDatabaseHas('stores', ['id' => $store->id]);
    }

    public function test_store_belongs_to_manager(): void
    {
        $manager = Customer::factory()->create();
        $store = Store::factory()->create(['manager_id' => $manager->id]);

        $this->assertInstanceOf(Customer::class, $store->manager);
        $this->assertEquals($manager->id, $store->manager->id);
    }

    public function test_store_code_is_unique(): void
    {
        Store::factory()->create(['store_code' => 'ST-0001']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Store::factory()->create(['store_code' => 'ST-0001']);
    }

    public function test_casts_are_correct(): void
    {
        $store = new Store();
        $casts = $store->getCasts();

        $this->assertArrayHasKey('store_type', $casts);
        $this->assertArrayHasKey('status', $casts);
        $this->assertArrayHasKey('support_pickup', $casts);
        $this->assertArrayHasKey('support_delivery', $casts);
        $this->assertArrayHasKey('longitude', $casts);
        $this->assertArrayHasKey('latitude', $casts);
    }
}
