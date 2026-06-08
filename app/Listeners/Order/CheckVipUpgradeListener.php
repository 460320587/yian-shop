<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Domains\User\Models\Customer;
use App\Domains\Vip\Models\VipLevel;
use App\Events\OrderStatusChanged;

class CheckVipUpgradeListener
{
    public function handle(OrderStatusChanged $event): void
    {
        // Only process when order is completed (status 60)
        if ($event->newStatus !== 60) {
            return;
        }

        $customer = Customer::find($event->order->customer_id);
        if ($customer === null) {
            return;
        }

        $currentGrowValue = $customer->grow_value;

        // Find the highest VIP level where min_points <= grow_value
        $newLevel = VipLevel::where('min_points', '<=', $currentGrowValue)
            ->orderByDesc('level')
            ->value('level');

        if ($newLevel !== null && $newLevel > $customer->vip_level) {
            $customer->update(['vip_level' => $newLevel]);
        }
    }
}
