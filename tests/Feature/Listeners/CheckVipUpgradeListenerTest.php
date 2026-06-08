<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use App\Domains\Vip\Models\VipLevel;
use App\Events\OrderStatusChanged;
use App\Listeners\Order\CheckVipUpgradeListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckVipUpgradeListenerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\VipLevelSeeder::class);
    }

    public function test_it_upgrades_vip_when_grow_value_crosses_threshold(): void
    {
        // Level 0 -> Level 2 requires 100 grow_value
        $customer = Customer::factory()->create([
            'vip_level' => 0,
            'grow_value' => 50,
        ]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 10000, // 100 yuan
            'status' => 60,
        ]);

        // Pre-increase grow_value (as if AwardPointsOnPayment already ran)
        $customer->update(['grow_value' => 150]);

        $listener = new CheckVipUpgradeListener();
        $listener->handle(new OrderStatusChanged($order, 20, 60));

        $customer->refresh();
        $this->assertEquals(2, $customer->vip_level); // 150 >= 100 -> level 2
    }

    public function test_it_does_not_upgrade_when_below_next_threshold(): void
    {
        // Level 1 -> Level 2 requires 100 grow_value
        $customer = Customer::factory()->create([
            'vip_level' => 1,
            'grow_value' => 50,
        ]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 5000,
            'status' => 60,
        ]);

        $listener = new CheckVipUpgradeListener();
        $listener->handle(new OrderStatusChanged($order, 20, 60));

        $customer->refresh();
        $this->assertEquals(1, $customer->vip_level); // Still level 1, not enough for level 2
    }

    public function test_it_skips_when_status_is_not_completed(): void
    {
        $customer = Customer::factory()->create([
            'vip_level' => 0,
            'grow_value' => 999999,
        ]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 10000,
            'status' => 12,
        ]);

        $listener = new CheckVipUpgradeListener();
        $listener->handle(new OrderStatusChanged($order, 11, 12));

        $customer->refresh();
        $this->assertEquals(0, $customer->vip_level);
    }
}
