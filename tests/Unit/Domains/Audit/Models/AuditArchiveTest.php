<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Audit\Models;

use App\Domains\Audit\Models\AuditArchive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_archive(): void
    {
        $archive = AuditArchive::factory()->create();
        $this->assertDatabaseHas('audit_archives', ['id' => $archive->id]);
    }

    public function test_status_is_integer(): void
    {
        $archive = AuditArchive::factory()->create(['status' => 1]);
        $this->assertSame(1, $archive->status);
    }
}
