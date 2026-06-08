<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseBackupCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_backup_command_creates_sql_file(): void
    {
        $this->artisan('db:backup')
            ->assertSuccessful()
            ->execute();

        $files = Storage::disk('local')->files('backups/db');
        $this->assertCount(1, $files);
        $this->assertStringStartsWith('backups/db/yashop_', $files[0]);
        $this->assertStringEndsWith('.sql', $files[0]);
    }

    public function test_backup_command_supports_compression(): void
    {
        $this->artisan('db:backup', ['--compress' => true])
            ->assertSuccessful()
            ->execute();

        $files = Storage::disk('local')->files('backups/db');
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.sql.gz', $files[0]);
    }

    public function test_backup_command_uses_custom_filename_prefix(): void
    {
        $this->artisan('db:backup', ['--prefix' => 'daily'])
            ->assertSuccessful()
            ->execute();

        $files = Storage::disk('local')->files('backups/db');
        $this->assertStringStartsWith('backups/db/daily_', $files[0]);
    }

    public function test_backup_command_returns_failure_when_directory_not_writable(): void
    {
        Storage::shouldReceive('disk')->andReturnUsing(function () {
            $mock = \Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
            $mock->shouldReceive('makeDirectory')->andReturn(false);
            return $mock;
        });

        $this->artisan('db:backup')
            ->assertFailed()
            ->execute();
    }
}
