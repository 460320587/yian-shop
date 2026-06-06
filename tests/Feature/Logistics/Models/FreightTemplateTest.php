<?php

declare(strict_types=1);

namespace Tests\Feature\Logistics\Models;

use App\Domains\Logistics\Models\FreightTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreightTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_freight_template(): void
    {
        $template = FreightTemplate::factory()->create();

        $this->assertDatabaseHas('freight_templates', ['id' => $template->id]);
    }

    public function test_belongs_to_carrier(): void
    {
        $template = FreightTemplate::factory()->create();

        $this->assertNotNull($template->carrier);
    }

    public function test_calculation_type_is_integer(): void
    {
        $template = FreightTemplate::factory()->create(['calculation_type' => 2]);

        $this->assertSame(2, $template->calculation_type);
    }

    public function test_regions_is_cast_to_array(): void
    {
        $template = FreightTemplate::factory()->create(['regions' => [['province' => '广东', 'surcharge' => 5.00]]]);

        $this->assertIsArray($template->regions);
    }

    public function test_status_is_integer(): void
    {
        $template = FreightTemplate::factory()->create(['status' => 0]);

        $this->assertSame(0, $template->status);
    }
}
