<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Models;

use App\Domains\User\Models\Customer;
use App\Domains\User\Models\SubAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_sub_account(): void
    {
        $sub = SubAccount::factory()->create();

        $this->assertDatabaseHas('sub_accounts', ['id' => $sub->id]);
    }

    public function test_sub_account_belongs_to_parent(): void
    {
        $parent = Customer::factory()->create();
        $sub = SubAccount::factory()->create(['parent_id' => $parent->id]);

        $this->assertInstanceOf(Customer::class, $sub->parent);
        $this->assertEquals($parent->id, $sub->parent->id);
    }

    public function test_password_hash_is_hidden(): void
    {
        $sub = SubAccount::factory()->create(['password_hash' => 'hashed']);
        $array = $sub->toArray();

        $this->assertArrayNotHasKey('password_hash', $array);
    }

    public function test_admin_has_all_permissions(): void
    {
        $sub = SubAccount::factory()->make(['sub_permission' => 0]);

        $this->assertTrue($sub->hasPermission(1));
        $this->assertTrue($sub->hasPermission(2));
        $this->assertTrue($sub->hasPermission(4));
        $this->assertTrue($sub->hasPermission(8));
        $this->assertTrue($sub->hasPermission(16));
        $this->assertTrue($sub->isAdmin());
    }

    public function test_permission_bitmask_works(): void
    {
        // 权限 5 = 客服(1) + 下单(4)
        $sub = SubAccount::factory()->make(['sub_permission' => 5]);

        $this->assertTrue($sub->hasPermission(1));
        $this->assertFalse($sub->hasPermission(2));
        $this->assertTrue($sub->hasPermission(4));
        $this->assertFalse($sub->hasPermission(8));
        $this->assertFalse($sub->isAdmin());
    }

    public function test_permissions_json_is_cast_to_array(): void
    {
        $sub = SubAccount::factory()->create(['permissions_json' => ['read', 'write']]);

        $this->assertIsArray($sub->permissions_json);
        $this->assertContains('read', $sub->permissions_json);
    }
}
