<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\User\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAddressSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_seeder_creates_addresses(): void
    {
        $this->seed([
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\CustomerAddressSeeder::class,
        ]);

        $this->assertDatabaseCount('customer_addresses', 5);
        $this->assertDatabaseHas('customer_addresses', ['province_name' => '河南省', 'city_name' => '郑州市']);
        $this->assertDatabaseHas('customer_addresses', ['province_name' => '广东省', 'city_name' => '深圳市']);
    }

    public function test_address_seeder_has_default_addresses(): void
    {
        $this->seed([
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\CustomerAddressSeeder::class,
        ]);

        $defaultCount = CustomerAddress::where('is_default', true)->count();
        $this->assertGreaterThanOrEqual(3, $defaultCount);
    }

    public function test_customer_can_have_multiple_addresses(): void
    {
        $this->seed([
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\CustomerAddressSeeder::class,
        ]);

        $zhangsanAddresses = CustomerAddress::where('contact_name', '张三')->count();
        $this->assertEquals(2, $zhangsanAddresses);
    }
}
