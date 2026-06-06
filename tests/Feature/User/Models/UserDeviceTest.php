<?php

declare(strict_types=1);

namespace Tests\Feature\User\Models;

use App\Domains\User\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_device(): void
    {
        $device = UserDevice::factory()->create();

        $this->assertDatabaseHas('user_devices', ['id' => $device->id]);
    }

    public function test_belongs_to_user(): void
    {
        $device = UserDevice::factory()->create();

        $this->assertNotNull($device->user);
    }

    public function test_user_device_is_unique(): void
    {
        $device = UserDevice::factory()->create();

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        UserDevice::factory()->create([
            'user_id' => $device->user_id,
            'device_id' => $device->device_id,
        ]);
    }

    public function test_is_current_is_integer(): void
    {
        $device = UserDevice::factory()->create(['is_current' => 1]);

        $this->assertSame(1, $device->is_current);
    }

    public function test_platform_values(): void
    {
        $device = UserDevice::factory()->create(['platform' => 'ios']);

        $this->assertSame('ios', $device->platform);
    }
}
