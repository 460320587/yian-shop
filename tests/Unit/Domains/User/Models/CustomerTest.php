<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Models;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Enterprise\Models\CustomerBrand;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_via_factory(): void
    {
        $customer = Customer::factory()->create();

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertNotNull($customer->phone);
    }

    /** @test */
    public function it_casts_balance_to_money(): void
    {
        $customer = Customer::factory()->create(['balance' => 50000]);

        $this->assertInstanceOf(Money::class, $customer->balance);
        $this->assertSame(50000, $customer->balance->amount);
        $this->assertSame(500.0, $customer->balance->toYuan());
    }

    /** @test */
    public function it_has_many_addresses(): void
    {
        $customer = Customer::factory()->create();
        CustomerAddress::factory()->count(3)->create(['customer_id' => $customer->id]);

        $this->assertCount(3, $customer->addresses);
        $this->assertInstanceOf(CustomerAddress::class, $customer->addresses->first());
    }

    /** @test */
    public function it_has_many_brands(): void
    {
        $customer = Customer::factory()->create();
        CustomerBrand::factory()->count(2)->create(['customer_id' => $customer->id]);

        $this->assertCount(2, $customer->brands);
        $this->assertInstanceOf(CustomerBrand::class, $customer->brands->first());
    }

    /** @test */
    public function it_has_many_orders(): void
    {
        $customer = Customer::factory()->create();
        Order::factory()->count(2)->create(['customer_id' => $customer->id]);

        $this->assertCount(2, $customer->orders);
        $this->assertInstanceOf(Order::class, $customer->orders->first());
    }

    /** @test */
    public function it_hides_password_in_serialization(): void
    {
        $customer = Customer::factory()->create(['password' => 'secret']);
        $array = $customer->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    /** @test */
    public function it_responds_to_active_scope(): void
    {
        Customer::factory()->count(2)->create(['status' => 1]);
        Customer::factory()->create(['status' => 0]);

        $this->assertCount(2, Customer::active()->get());
    }

    /** @test */
    public function it_responds_to_recent_scope(): void
    {
        Customer::factory()->create(['created_at' => now()->subDays(10)]);
        Customer::factory()->create(['created_at' => now()->subDay()]);

        $this->assertCount(1, Customer::recent(5)->get());
    }
}
