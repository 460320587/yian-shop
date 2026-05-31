<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Models;

use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAddressTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_via_factory(): void
    {
        $address = CustomerAddress::factory()->create();

        $this->assertDatabaseHas('customer_addresses', ['id' => $address->id]);
    }

    /** @test */
    public function it_belongs_to_customer(): void
    {
        $address = CustomerAddress::factory()->create();

        $this->assertInstanceOf(Customer::class, $address->customer);
    }

    /** @test */
    public function it_casts_is_default_to_boolean(): void
    {
        $address = CustomerAddress::factory()->create(['is_default' => 1]);

        $this->assertTrue($address->is_default);
    }
}
