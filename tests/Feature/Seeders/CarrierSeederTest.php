<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\Logistics\Models\Carrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarrierSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_carrier_seeder_creates_carriers(): void
    {
        $this->seed(\Database\Seeders\CarrierSeeder::class);

        $this->assertDatabaseCount('carriers', 3);
        $this->assertDatabaseHas('carriers', ['code' => 'SF', 'name' => '顺丰速运']);
        $this->assertDatabaseHas('carriers', ['code' => 'ZTO', 'name' => '中通快递']);
    }

    public function test_carrier_seeder_has_default_carrier(): void
    {
        $this->seed(\Database\Seeders\CarrierSeeder::class);

        $this->assertDatabaseHas('carriers', ['code' => 'SF', 'is_default' => 1]);
    }

    public function test_carrier_configs_are_valid_json(): void
    {
        $this->seed(\Database\Seeders\CarrierSeeder::class);

        $carrier = Carrier::where('code', 'SF')->first();
        $this->assertNotNull($carrier);
        $this->assertIsArray($carrier->config);
    }
}
