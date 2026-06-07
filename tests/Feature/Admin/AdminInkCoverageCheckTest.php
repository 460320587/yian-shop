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

class AdminInkCoverageCheckTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_ink_coverage_checks(): void
    {
        $this->authAdmin();
        InkCoverageCheck::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/ink-coverage-checks');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_id', 'file_id', 'check_type', 'ink_type', 'coverage_c', 'coverage_m', 'coverage_y', 'coverage_k', 'total_coverage', 'check_result', 'checked_at'],
                ],
            ]);
    }

    public function test_admin_can_list_ink_coverage_checks_with_pagination(): void
    {
        $this->authAdmin();
        InkCoverageCheck::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/admin/ink-coverage-checks?page=1&per_page=10');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_admin_can_create_ink_coverage_check(): void
    {
        $admin = $this->authAdmin();
        $order = Order::factory()->create();
        $file = OrderFile::factory()->create(['order_id' => $order->id]);

        $response = $this->postJson('/api/v1/admin/ink-coverage-checks', [
            'order_id' => $order->id,
            'file_id' => $file->id,
            'check_type' => 1,
            'ink_type' => 'CMYK',
            'coverage_c' => 12.5,
            'coverage_m' => 34.2,
            'coverage_y' => 8.1,
            'coverage_k' => 45.0,
            'total_coverage' => 99.8,
            'check_result' => 1,
            'check_report' => ['dpi' => 300, 'color_mode' => 'CMYK'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.check_type', 1)
            ->assertJsonPath('data.check_result', 1);

        $this->assertDatabaseHas('ink_coverage_checks', [
            'order_id' => $order->id,
            'file_id' => $file->id,
            'check_type' => 1,
            'ink_type' => 'CMYK',
            'coverage_c' => 12.5,
            'total_coverage' => 99.8,
            'check_result' => 1,
            'checked_by' => null,
        ]);
    }

    public function test_admin_can_update_ink_coverage_check(): void
    {
        $this->authAdmin();
        $check = InkCoverageCheck::factory()->create([
            'coverage_c' => 10.0,
            'check_result' => 0,
        ]);

        $response = $this->putJson("/api/v1/admin/ink-coverage-checks/{$check->id}", [
            'coverage_c' => 25.5,
            'coverage_m' => 40.0,
            'check_result' => 1,
            'check_report' => ['dpi' => 300, 'updated' => true],
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('ink_coverage_checks', [
            'id' => $check->id,
            'coverage_c' => 25.5,
            'coverage_m' => 40.0,
            'check_result' => 1,
        ]);
    }

    public function test_admin_can_delete_ink_coverage_check(): void
    {
        $this->authAdmin();
        $check = InkCoverageCheck::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/ink-coverage-checks/{$check->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertSoftDeleted('ink_coverage_checks', ['id' => $check->id]);
    }

    public function test_create_requires_valid_order(): void
    {
        $this->authAdmin();
        $file = OrderFile::factory()->create();

        $response = $this->postJson('/api/v1/admin/ink-coverage-checks', [
            'order_id' => 99999,
            'file_id' => $file->id,
            'check_type' => 1,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('code', ErrorCode::NOT_FOUND->value);
    }

    public function test_create_requires_valid_file(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();

        $response = $this->postJson('/api/v1/admin/ink-coverage-checks', [
            'order_id' => $order->id,
            'file_id' => 99999,
            'check_type' => 1,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('code', ErrorCode::NOT_FOUND->value);
    }

    public function test_create_requires_check_type(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();
        $file = OrderFile::factory()->create(['order_id' => $order->id]);

        $response = $this->postJson('/api/v1/admin/ink-coverage-checks', [
            'order_id' => $order->id,
            'file_id' => $file->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_update_nonexistent_check_returns_404(): void
    {
        $this->authAdmin();

        $response = $this->putJson('/api/v1/admin/ink-coverage-checks/99999', [
            'check_result' => 1,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('code', ErrorCode::NOT_FOUND->value);
    }

    public function test_delete_nonexistent_check_returns_404(): void
    {
        $this->authAdmin();

        $response = $this->deleteJson('/api/v1/admin/ink-coverage-checks/99999');

        $response->assertStatus(404)
            ->assertJsonPath('code', ErrorCode::NOT_FOUND->value);
    }

    public function test_unauthenticated_cannot_create_check(): void
    {
        $this->postJson('/api/v1/admin/ink-coverage-checks', [])
            ->assertUnauthorized();
    }
}
