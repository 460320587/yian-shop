<?php

declare(strict_types=1);

namespace Tests\Feature\Common\Models;

use App\Domains\Common\Models\Upload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_upload(): void
    {
        $upload = Upload::factory()->create();

        $this->assertDatabaseHas('uploads', ['id' => $upload->id]);
    }

    public function test_belongs_to_user(): void
    {
        $upload = Upload::factory()->create();

        $this->assertNotNull($upload->user);
    }

    public function test_file_size_is_integer(): void
    {
        $upload = Upload::factory()->create(['file_size' => 1024]);

        $this->assertSame(1024, $upload->file_size);
    }

    public function test_status_is_integer(): void
    {
        $upload = Upload::factory()->create(['status' => 0]);

        $this->assertSame(0, $upload->status);
    }

    public function test_user_id_can_be_null(): void
    {
        $upload = Upload::factory()->create(['user_id' => null]);

        $this->assertNull($upload->user_id);
    }
}
