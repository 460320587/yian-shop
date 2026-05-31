<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Enterprise\Models;

use App\Domains\Enterprise\Models\CustomerBrand;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBrandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_via_factory(): void
    {
        $brand = CustomerBrand::factory()->create();

        $this->assertDatabaseHas('customer_brands', ['id' => $brand->id]);
    }

    /** @test */
    public function it_belongs_to_customer(): void
    {
        $brand = CustomerBrand::factory()->create();

        $this->assertInstanceOf(Customer::class, $brand->customer);
    }

    /** @test */
    public function it_casts_dates_correctly(): void
    {
        $brand = CustomerBrand::factory()->create([
            'valid_start' => '2025-01-01',
            'valid_end' => '2026-01-01',
        ]);

        $this->assertSame('2025-01-01', $brand->valid_start->format('Y-m-d'));
        $this->assertSame('2026-01-01', $brand->valid_end->format('Y-m-d'));
    }
}
