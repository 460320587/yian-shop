<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Audit\Models;

use App\Domains\Audit\Models\DataChangeLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataChangeLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_log(): void
    {
        $log = DataChangeLog::factory()->create();
        $this->assertDatabaseHas('data_change_logs', ['id' => $log->id]);
    }

    public function test_action_type_is_integer(): void
    {
        $log = DataChangeLog::factory()->create(['action_type' => 2]);
        $this->assertSame(2, $log->action_type);
    }
}
