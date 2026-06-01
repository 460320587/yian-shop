<?php

declare(strict_types=1);

namespace Tests\Feature\Points;

use App\Domains\Points\Models\CustomerPointsLog;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointsTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(array $attributes = []): Customer
    {
        $customer = Customer::factory()->create($attributes);
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_get_points_balance(): void
    {
        $customer = $this->authCustomer([
            'points' => 2580,
            'grow_value' => 5000,
        ]);

        $response = $this->getJson('/api/v1/points');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.points', 2580)
            ->assertJsonPath('data.grow_value', 5000);
    }

    public function test_user_can_get_points_records(): void
    {
        $customer = $this->authCustomer(['points' => 500]);
        CustomerPointsLog::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'type' => 1,
        ]);
        CustomerPointsLog::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'type' => 4,
        ]);
        $otherCustomer = Customer::factory()->create();
        CustomerPointsLog::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->getJson('/api/v1/points/records');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 5);
    }

    public function test_records_can_filter_by_type(): void
    {
        $customer = $this->authCustomer(['points' => 500]);
        CustomerPointsLog::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'type' => 1,
        ]);
        CustomerPointsLog::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'type' => 4,
        ]);

        $response = $this->getJson('/api/v1/points/records?type=1');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_user_can_only_see_own_records(): void
    {
        $customer = $this->authCustomer(['points' => 500]);
        CustomerPointsLog::factory()->create(['customer_id' => $customer->id]);
        $otherCustomer = Customer::factory()->create();
        CustomerPointsLog::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->getJson('/api/v1/points/records');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 1);
    }
}
