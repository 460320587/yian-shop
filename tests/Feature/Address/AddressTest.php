<?php

declare(strict_types=1);

namespace Tests\Feature\Address;

use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_get_address_list(): void
    {
        $customer = $this->authCustomer();
        CustomerAddress::factory()->count(2)->create(['customer_id' => $customer->id]);
        $otherCustomer = Customer::factory()->create();
        CustomerAddress::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->getJson('/api/v1/addresses');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_user_can_add_address(): void
    {
        $customer = $this->authCustomer();

        $response = $this->postJson('/api/v1/addresses', [
            'contact_name' => '张三',
            'contact_phone' => '13800138000',
            'province_name' => '北京市',
            'city_name' => '北京市',
            'county_name' => '朝阳区',
            'detail_address' => '建国路88号',
            'zip_code' => '100000',
            'is_default' => true,
            'tag' => '公司',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.contact_name', '张三')
            ->assertJsonPath('data.full_address', '北京市北京市朝阳区建国路88号')
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'contact_phone' => '13800138000',
            'tag' => '公司',
        ]);
    }

    public function test_add_address_sets_default_correctly(): void
    {
        $customer = $this->authCustomer();
        $first = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
        ]);

        $this->postJson('/api/v1/addresses', [
            'contact_name' => '李四',
            'contact_phone' => '13800138001',
            'province_name' => '上海市',
            'city_name' => '上海市',
            'county_name' => '浦东新区',
            'detail_address' => '世纪大道1号',
            'is_default' => true,
        ]);

        $first->refresh();
        $this->assertFalse((bool) $first->is_default);
        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'contact_name' => '李四',
            'is_default' => true,
        ]);
    }

    public function test_user_can_update_address(): void
    {
        $customer = $this->authCustomer();
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->id]);

        $response = $this->putJson('/api/v1/addresses/' . $address->id, [
            'contact_name' => '王五',
            'contact_phone' => '13800138002',
            'province_name' => '广东省',
            'city_name' => '深圳市',
            'county_name' => '南山区',
            'detail_address' => '科技园路1号',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.contact_name', '王五');
    }

    public function test_user_can_set_address_default(): void
    {
        $customer = $this->authCustomer();
        $first = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'is_default' => true]);
        $second = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'is_default' => false]);

        $response = $this->putJson('/api/v1/addresses/' . $second->id . '/default');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $first->refresh();
        $second->refresh();
        $this->assertFalse((bool) $first->is_default);
        $this->assertTrue((bool) $second->is_default);
    }

    public function test_user_can_delete_address(): void
    {
        $customer = $this->authCustomer();
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->id]);

        $response = $this->deleteJson('/api/v1/addresses/' . $address->id);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseMissing('customer_addresses', ['id' => $address->id]);
    }

    public function test_delete_default_address_auto_selects_earliest(): void
    {
        $customer = $this->authCustomer();
        $first = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
            'created_at' => now()->subDay(),
        ]);
        $second = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => false,
            'created_at' => now(),
        ]);

        $this->deleteJson('/api/v1/addresses/' . $first->id);

        $second->refresh();
        $this->assertTrue((bool) $second->is_default);
    }

    public function test_user_cannot_modify_others_address(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $address = CustomerAddress::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->putJson('/api/v1/addresses/' . $address->id, [
            'contact_name' => '黑客',
        ]);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_addresses(): void
    {
        $response = $this->getJson('/api/v1/addresses');
        $response->assertStatus(401);
    }
}
