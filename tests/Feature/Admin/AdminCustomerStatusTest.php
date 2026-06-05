<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerStatusTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_disable_customer(): void
    {
        $this->authAdmin();
        $customer = Customer::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/customers/{$customer->id}/toggle-status");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 0);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'status' => 0,
        ]);
    }

    public function test_admin_can_enable_customer(): void
    {
        $this->authAdmin();
        $customer = Customer::factory()->create(['status' => 0]);

        $response = $this->putJson("/api/v1/admin/customers/{$customer->id}/toggle-status");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 1);
    }

    public function test_admin_cannot_toggle_nonexistent_customer(): void
    {
        $this->authAdmin();

        $response = $this->putJson('/api/v1/admin/customers/99999/toggle-status');

        $response->assertNotFound()
            ->assertJsonPath('code', 404);
    }

    public function test_unauthenticated_cannot_toggle_customer_status(): void
    {
        $customer = Customer::factory()->create();
        $this->putJson("/api/v1/admin/customers/{$customer->id}/toggle-status")
            ->assertUnauthorized();
    }
}
