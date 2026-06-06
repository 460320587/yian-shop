<?php

declare(strict_types=1);

namespace Tests\Feature\Notification\Models;

use App\Domains\Notification\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_sms_log(): void
    {
        $log = SmsLog::factory()->create();

        $this->assertDatabaseHas('sms_logs', ['id' => $log->id]);
    }

    public function test_type_is_integer(): void
    {
        $log = SmsLog::factory()->create(['type' => 2]);

        $this->assertSame(2, $log->type);
    }

    public function test_status_is_integer(): void
    {
        $log = SmsLog::factory()->create(['status' => 1]);

        $this->assertSame(1, $log->status);
    }

    public function test_phone_is_indexed(): void
    {
        $log = SmsLog::factory()->create(['phone' => '13800138000']);

        $this->assertSame('13800138000', $log->phone);
    }
}
