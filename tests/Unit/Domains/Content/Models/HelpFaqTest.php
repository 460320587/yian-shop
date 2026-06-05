<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Content\Models;

use App\Domains\Content\Models\HelpFaq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpFaqTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_faq(): void
    {
        $faq = HelpFaq::factory()->create();
        $this->assertDatabaseHas('help_faqs', ['id' => $faq->id]);
    }

    public function test_casts_are_correct(): void
    {
        $faq = new HelpFaq();
        $casts = $faq->getCasts();

        $this->assertArrayHasKey('view_count', $casts);
        $this->assertArrayHasKey('helpful_count', $casts);
        $this->assertArrayHasKey('not_helpful_count', $casts);
        $this->assertArrayHasKey('sort_order', $casts);
    }
}
