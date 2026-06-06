<?php

declare(strict_types=1);

namespace Tests\Feature\Logistics\Models;

use App\Domains\Logistics\Models\Carrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarrierTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_carrier(): void
    {
        $carrier = Carrier::factory()->create();

        $this->assertDatabaseHas('carriers', ['id' => $carrier->id]);
    }

    public function test_code_is_unique(): void
    {
        Carrier::factory()->create(['code' => 'sf']);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Carrier::factory()->create(['code' => 'sf']);
    }

    public function test_status_is_integer(): void
    {
        $carrier = Carrier::factory()->create(['status' => 0]);

        $this->assertSame(0, $carrier->status);
    }

    public function test_config_is_cast_to_array(): void
    {
        $carrier = Carrier::factory()->create(['config' => ['key' => 'value']]);

        $this->assertIsArray($carrier->config);
        $this->assertSame('value', $carrier->config['key']);
    }
}
