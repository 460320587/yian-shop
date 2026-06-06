<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Notification\Models;

use App\Domains\Notification\Models\NotificationLog;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_log(): void
    {
        $log = NotificationLog::factory()->create();
        $this->assertDatabaseHas('notification_logs', ['id' => $log->id]);
    }

    public function test_log_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $log = NotificationLog::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $log->customer);
    }

    public function test_variables_is_cast_to_array(): void
    {
        $log = NotificationLog::factory()->create(['variables' => ['order_no' => '123']]);
        $this->assertIsArray($log->variables);
    }
}
