<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Models;

use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_preference(): void
    {
        $pref = CustomerPreference::factory()->create();
        $this->assertDatabaseHas('customer_preferences', ['id' => $pref->id]);
    }

    public function test_preference_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $pref = CustomerPreference::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $pref->customer);
        $this->assertEquals($customer->id, $pref->customer->id);
    }

    public function test_customer_id_is_unique(): void
    {
        $customer = Customer::factory()->create();
        CustomerPreference::factory()->create(['customer_id' => $customer->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        CustomerPreference::factory()->create(['customer_id' => $customer->id]);
    }

    public function test_casts_are_correct(): void
    {
        $pref = new CustomerPreference();
        $casts = $pref->getCasts();

        $this->assertArrayHasKey('product_layout_type', $casts);
        $this->assertArrayHasKey('category_grid_type', $casts);
        $this->assertArrayHasKey('pay_now', $casts);
    }
}
