<?php

declare(strict_types=1);

namespace Tests\Feature\Notification\Models;

use App\Domains\Notification\Models\NotificationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_template(): void
    {
        $template = NotificationTemplate::factory()->create();

        $this->assertDatabaseHas('notification_templates', ['id' => $template->id]);
    }

    public function test_code_is_unique(): void
    {
        NotificationTemplate::factory()->create(['code' => 'order_paid']);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        NotificationTemplate::factory()->create(['code' => 'order_paid']);
    }

    public function test_channels_is_cast_to_array(): void
    {
        $template = NotificationTemplate::factory()->create(['channels' => ['in_app', 'sms']]);

        $this->assertIsArray($template->channels);
        $this->assertContains('sms', $template->channels);
    }

    public function test_status_is_integer(): void
    {
        $template = NotificationTemplate::factory()->create(['status' => 0]);

        $this->assertSame(0, $template->status);
    }
}
