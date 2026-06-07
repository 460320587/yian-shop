<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Order\Models\InkCoverageCheck;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderFile;
use App\Domains\Order\Models\ProductionSchedule;
use App\Domains\Payment\Models\RefundRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderDetailEnhancedTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_order_detail_includes_production_schedules(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();
        ProductionSchedule::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data.production_schedules')
            ->assertJsonStructure([
                'data' => [
                    'production_schedules' => [
                        '*' => ['id', 'order_id', 'schedule_date', 'process_name', 'status', 'progress'],
                    ],
                ],
            ]);
    }

    public function test_order_detail_includes_order_files(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();
        OrderFile::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data.order_files')
            ->assertJsonStructure([
                'data' => [
                    'order_files' => [
                        '*' => ['id', 'order_id', 'file_name', 'file_url', 'file_size', 'status'],
                    ],
                ],
            ]);
    }

    public function test_order_detail_includes_ink_coverage_checks(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();
        $file = OrderFile::factory()->create(['order_id' => $order->id]);
        InkCoverageCheck::factory()->count(2)->create(['order_id' => $order->id, 'file_id' => $file->id]);

        $response = $this->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data.ink_coverage_checks')
            ->assertJsonStructure([
                'data' => [
                    'ink_coverage_checks' => [
                        '*' => ['id', 'order_id', 'file_id', 'check_type', 'ink_type', 'check_result'],
                    ],
                ],
            ]);
    }

    public function test_order_detail_includes_refund_records(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();
        RefundRecord::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data.refund_records')
            ->assertJsonStructure([
                'data' => [
                    'refund_records' => [
                        '*' => ['id', 'order_id', 'refund_no', 'amount', 'status'],
                    ],
                ],
            ]);
    }

    public function test_order_detail_returns_empty_arrays_when_no_associations(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();

        $response = $this->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(0, 'data.production_schedules')
            ->assertJsonCount(0, 'data.order_files')
            ->assertJsonCount(0, 'data.ink_coverage_checks')
            ->assertJsonCount(0, 'data.refund_records');
    }
}
