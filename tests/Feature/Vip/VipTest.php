<?php

declare(strict_types=1);

namespace Tests\Feature\Vip;

use App\Domains\User\Models\Customer;
use App\Domains\Vip\Models\VipLevel;
use Database\Seeders\VipLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VipLevelSeeder::class);
    }

    private function authCustomer(array $attributes = []): Customer
    {
        $customer = Customer::factory()->create($attributes);
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_get_vip_info(): void
    {
        $customer = $this->authCustomer([
            'vip_level' => 3,
            'grow_value' => 1500,
        ]);

        $response = $this->getJson('/api/v1/vip/info');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.current_level', 3)
            ->assertJsonPath('data.current_grow_value', 1500)
            ->assertJsonPath('data.next_level', 4)
            ->assertJsonPath('data.next_level_points', 20000)
            ->assertJsonPath('data.progress_percent', 2.63);
    }

    public function test_vip_info_for_max_level(): void
    {
        $customer = $this->authCustomer([
            'vip_level' => 8,
            'grow_value' => 600000,
        ]);

        $response = $this->getJson('/api/v1/vip/info');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.current_level', 8)
            ->assertJsonPath('data.progress_percent', 100)
            ->assertJsonPath('data.next_level', null);
    }

    public function test_user_can_get_vip_levels(): void
    {
        $this->authCustomer();

        $response = $this->getJson('/api/v1/vip/levels');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(9, 'data');
    }

    public function test_user_can_get_vip_discounts(): void
    {
        $customer = $this->authCustomer([
            'vip_level' => 6,
            'grow_value' => 100000,
        ]);

        $response = $this->getJson('/api/v1/vip/discounts');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.vip_level', 6)
            ->assertJsonPath('data.print_discount', 0.93)
            ->assertJsonPath('data.deadline_extension', 30);
    }
}
