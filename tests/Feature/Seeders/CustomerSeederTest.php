<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_seeder_creates_customers(): void
    {
        $this->seed(\Database\Seeders\CustomerSeeder::class);

        $this->assertDatabaseCount('customers', 5);
        $this->assertDatabaseHas('customers', ['phone' => '13800138000', 'nickname' => '张三']);
        $this->assertDatabaseHas('customers', ['phone' => '13800138001', 'nickname' => '李四']);
    }

    public function test_customer_seeder_has_vip_levels(): void
    {
        $this->seed(\Database\Seeders\CustomerSeeder::class);

        $this->assertDatabaseHas('customers', ['phone' => '13800138000', 'vip_level' => 3]);
        $this->assertDatabaseHas('customers', ['phone' => '13800138004', 'vip_level' => 8]);
    }

    public function test_customer_seeder_has_disabled_customer(): void
    {
        $this->seed(\Database\Seeders\CustomerSeeder::class);

        $this->assertDatabaseHas('customers', ['phone' => '13800138003', 'status' => 0]);
    }

    public function test_customer_password_is_hashed(): void
    {
        $this->seed(\Database\Seeders\CustomerSeeder::class);

        $customer = Customer::where('phone', '13800138000')->first();
        $this->assertNotNull($customer);
        $this->assertTrue(Hash::check('password', $customer->password));
    }
}
