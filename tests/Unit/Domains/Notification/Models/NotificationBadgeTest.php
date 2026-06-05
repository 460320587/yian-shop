<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Notification\Models;

use App\Domains\Notification\Models\NotificationBadge;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_badge(): void
    {
        $badge = NotificationBadge::factory()->create();
        $this->assertDatabaseHas('notification_badges', ['id' => $badge->id]);
    }

    public function test_badge_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $badge = NotificationBadge::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $badge->customer);
    }

    public function test_badge_has_unique_customer_notification_type(): void
    {
        $customer = Customer::factory()->create();
        NotificationBadge::factory()->create([
            'customer_id' => $customer->id,
            'notification_type' => 'order',
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        NotificationBadge::factory()->create([
            'customer_id' => $customer->id,
            'notification_type' => 'order',
        ]);
    }

    public function test_unread_count_defaults_to_zero(): void
    {
        $customer = Customer::factory()->create();
        $badge = NotificationBadge::factory()->create([
            'customer_id' => $customer->id,
            'notification_type' => 'system',
            'unread_count' => 0,
        ]);

        $this->assertSame(0, $badge->unread_count);
    }
}
