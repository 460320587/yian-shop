<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Dropship\Models;

use App\Domains\Dropship\Models\PlatformShop;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_platform_shop(): void
    {
        $shop = PlatformShop::factory()->create();

        $this->assertDatabaseHas('platform_shops', ['id' => $shop->id]);
    }

    public function test_platform_shop_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $shop = PlatformShop::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $shop->customer);
        $this->assertEquals($customer->id, $shop->customer->id);
    }

    public function test_auth_token_is_hidden(): void
    {
        $shop = PlatformShop::factory()->create(['auth_token' => 'secret_token']);
        $array = $shop->toArray();

        $this->assertArrayNotHasKey('auth_token', $array);
    }

    public function test_casts_are_correct(): void
    {
        $shop = new PlatformShop();
        $casts = $shop->getCasts();

        $this->assertArrayHasKey('platform', $casts);
        $this->assertArrayHasKey('shop_auth_status', $casts);
        $this->assertArrayHasKey('expire_time', $casts);
    }
}
