<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupDatabaseBackupsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Carbon::setTestNow('2026-06-09 00:00:00');
    }

    public function test_cleanup_removes_backups_older_than_keep_days(): void
    {
        Storage::disk('local')->put('backups/db/old_backup.sql', 'content');
        Storage::disk('local')->put('backups/db/recent_backup.sql', 'content');

        // 模拟文件修改时间
        touch(Storage::disk('local')->path('backups/db/old_backup.sql'), Carbon::now()->subDays(40)->timestamp);
        touch(Storage::disk('local')->path('backups/db/recent_backup.sql'), Carbon::now()->subDays(10)->timestamp);

        $this->artisan('db:backup:cleanup', ['--keep-days' => 30])
            ->assertSuccessful()
            ->execute();

        $this->assertFalse(Storage::disk('local')->exists('backups/db/old_backup.sql'));
        $this->assertTrue(Storage::disk('local')->exists('backups/db/recent_backup.sql'));
    }

    public function test_cleanup_keeps_all_backups_within_keep_days(): void
    {
        Storage::disk('local')->put('backups/db/backup1.sql', 'content');
        Storage::disk('local')->put('backups/db/backup2.sql', 'content');

        $this->artisan('db:backup:cleanup', ['--keep-days' => 30])
            ->assertSuccessful()
            ->execute();

        $this->assertTrue(Storage::disk('local')->exists('backups/db/backup1.sql'));
        $this->assertTrue(Storage::disk('local')->exists('backups/db/backup2.sql'));
    }

    public function test_cleanup_respects_different_keep_days(): void
    {
        Storage::disk('local')->put('backups/db/backup_35d.sql', 'content');
        touch(Storage::disk('local')->path('backups/db/backup_35d.sql'), Carbon::now()->subDays(35)->timestamp);

        $this->artisan('db:backup:cleanup', ['--keep-days' => 7])
            ->assertSuccessful()
            ->execute();

        $this->assertFalse(Storage::disk('local')->exists('backups/db/backup_35d.sql'));
    }
}
