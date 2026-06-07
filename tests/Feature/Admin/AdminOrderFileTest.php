<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Order\Models\InkCoverageCheck;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderFile;
use App\Support\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderFileTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_order_files(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();
        OrderFile::factory()->count(3)->create(['order_id' => $order->id]);

        $response = $this->getJson("/api/v1/admin/orders/{$order->id}/files");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_delete_order_file(): void
    {
        $this->authAdmin();
        $file = OrderFile::factory()->create(['status' => 1]);

        $response = $this->deleteJson("/api/v1/admin/order-files/{$file->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('order_files', [
            'id' => $file->id,
            'status' => 0,
        ]);
    }

    public function test_admin_cannot_delete_nonexistent_file(): void
    {
        $this->authAdmin();

        $response = $this->deleteJson('/api/v1/admin/order-files/99999');

        $response->assertStatus(404)
            ->assertJsonPath('code', ErrorCode::NOT_FOUND->value);
    }

    public function test_admin_can_list_ink_coverage_checks(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();
        InkCoverageCheck::factory()->count(2)->create(['order_id' => $order->id]);
        InkCoverageCheck::factory()->create(); // other order

        $response = $this->getJson("/api/v1/admin/orders/{$order->id}/ink-coverage-checks");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_id', 'file_id', 'check_type', 'ink_type', 'coverage_c', 'coverage_m', 'coverage_y', 'coverage_k', 'total_coverage', 'check_result'],
                ],
            ]);
    }

    public function test_admin_can_view_ink_coverage_check_detail(): void
    {
        $this->authAdmin();
        $check = InkCoverageCheck::factory()->create();

        $response = $this->getJson("/api/v1/admin/ink-coverage-checks/{$check->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $check->id)
            ->assertJsonPath('data.order_id', $check->order_id);
    }

    public function test_unauthenticated_cannot_access_admin_order_files(): void
    {
        $order = Order::factory()->create();
        $this->getJson("/api/v1/admin/orders/{$order->id}/files")
            ->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_access_ink_coverage_checks(): void
    {
        $order = Order::factory()->create();
        $this->getJson("/api/v1/admin/orders/{$order->id}/ink-coverage-checks")
            ->assertUnauthorized();
    }
}
