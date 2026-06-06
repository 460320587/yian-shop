<?php

declare(strict_types=1);

namespace Tests\Feature\User\Models;

use App\Domains\User\Models\LoginLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_login_log(): void
    {
        $log = LoginLog::factory()->create();

        $this->assertDatabaseHas('login_logs', ['id' => $log->id]);
    }

    public function test_belongs_to_user(): void
    {
        $log = LoginLog::factory()->create();

        $this->assertNotNull($log->user);
    }

    public function test_type_is_integer(): void
    {
        $log = LoginLog::factory()->create(['type' => 2]);

        $this->assertSame(2, $log->type);
    }

    public function test_status_is_integer(): void
    {
        $log = LoginLog::factory()->create(['status' => 0]);

        $this->assertSame(0, $log->status);
    }

    public function test_user_id_can_be_null(): void
    {
        $log = LoginLog::factory()->create(['user_id' => null]);

        $this->assertNull($log->user_id);
    }
}
