<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\ProductionSchedule;
use App\Support\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductionScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_schedules(): void
    {
        $this->authAdmin();
        ProductionSchedule::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/production-schedules');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_id', 'schedule_date', 'process_name', 'status', 'progress', 'priority'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_filter_schedules_by_status(): void
    {
        $this->authAdmin();
        ProductionSchedule::factory()->create(['status' => 0]);
        ProductionSchedule::factory()->create(['status' => 1]);
        ProductionSchedule::factory()->create(['status' => 2]);

        $response = $this->getJson('/api/v1/admin/production-schedules?status=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(1, $response->json('data.0.status'));
    }

    public function test_admin_can_filter_schedules_by_order_id(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();
        ProductionSchedule::factory()->create(['order_id' => $order->id]);
        ProductionSchedule::factory()->create();

        $response = $this->getJson("/api/v1/admin/production-schedules?order_id={$order->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($order->id, $response->json('data.0.order_id'));
    }

    public function test_admin_can_view_schedule_detail(): void
    {
        $this->authAdmin();
        $schedule = ProductionSchedule::factory()->create();

        $response = $this->getJson("/api/v1/admin/production-schedules/{$schedule->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $schedule->id)
            ->assertJsonPath('data.order_id', $schedule->order_id)
            ->assertJsonStructure([
                'data' => ['id', 'order_id', 'schedule_date', 'process_name', 'status', 'progress', 'priority', 'created_at'],
            ]);
    }

    public function test_admin_can_create_schedule(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create(['status' => OrderStatus::Paid->value]);

        $response = $this->postJson('/api/v1/admin/production-schedules', [
            'order_id' => $order->id,
            'schedule_date' => now()->addDay()->toDateString(),
            'process_name' => '印刷',
            'priority' => 2,
            'estimated_hours' => 4.5,
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.order_id', $order->id);

        $this->assertDatabaseHas('production_schedules', [
            'order_id' => $order->id,
            'process_name' => '印刷',
            'status' => 0,
            'priority' => 2,
            'estimated_hours' => 4.5,
        ]);
    }

    public function test_create_schedule_transitions_paid_order_to_in_production(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create(['status' => OrderStatus::Paid->value]);

        $this->postJson('/api/v1/admin/production-schedules', [
            'order_id' => $order->id,
            'schedule_date' => now()->addDay()->toDateString(),
            'process_name' => '印刷',
            'priority' => 2,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::InProduction->value,
        ]);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Paid->value,
            'to_status' => OrderStatus::InProduction->value,
        ]);
    }

    public function test_create_schedule_does_not_transition_when_already_in_production(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create(['status' => OrderStatus::InProduction->value]);

        $this->postJson('/api/v1/admin/production-schedules', [
            'order_id' => $order->id,
            'schedule_date' => now()->addDay()->toDateString(),
            'process_name' => '覆膜',
            'priority' => 3,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::InProduction->value,
        ]);
    }

    public function test_admin_can_update_schedule(): void
    {
        $this->authAdmin();
        $schedule = ProductionSchedule::factory()->create([
            'process_name' => '印刷',
            'priority' => 3,
        ]);

        $response = $this->putJson("/api/v1/admin/production-schedules/{$schedule->id}", [
            'process_name' => '裁切',
            'priority' => 1,
            'estimated_hours' => 2.0,
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('production_schedules', [
            'id' => $schedule->id,
            'process_name' => '裁切',
            'priority' => 1,
            'estimated_hours' => 2.0,
        ]);
    }

    public function test_admin_can_update_schedule_progress(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create(['status' => OrderStatus::InProduction->value]);
        $schedule = ProductionSchedule::factory()->create([
            'order_id' => $order->id,
            'progress' => 30,
            'status' => 2,
        ]);

        $response = $this->putJson("/api/v1/admin/production-schedules/{$schedule->id}/progress", [
            'progress' => 75,
            'actual_hours' => 3.5,
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('production_schedules', [
            'id' => $schedule->id,
            'progress' => 75,
            'actual_hours' => 3.5,
        ]);
    }

    public function test_update_progress_to_100_transitions_order_to_production_complete(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create(['status' => OrderStatus::InProduction->value]);
        $schedule = ProductionSchedule::factory()->create([
            'order_id' => $order->id,
            'progress' => 80,
            'status' => 2,
        ]);

        $response = $this->putJson("/api/v1/admin/production-schedules/{$schedule->id}/progress", [
            'progress' => 100,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('production_schedules', [
            'id' => $schedule->id,
            'progress' => 100,
            'status' => 3, // 已完成
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::ProductionComplete->value,
        ]);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::InProduction->value,
            'to_status' => OrderStatus::ProductionComplete->value,
        ]);
    }

    public function test_update_progress_does_not_transition_when_order_not_in_production(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create(['status' => OrderStatus::Paid->value]);
        $schedule = ProductionSchedule::factory()->create([
            'order_id' => $order->id,
            'progress' => 80,
            'status' => 2,
        ]);

        $this->putJson("/api/v1/admin/production-schedules/{$schedule->id}/progress", [
            'progress' => 100,
        ]);

        // Order stays at Paid because it was never transitioned to InProduction
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Paid->value,
        ]);
    }

    public function test_admin_cannot_view_nonexistent_schedule(): void
    {
        $this->authAdmin();

        $response = $this->getJson('/api/v1/admin/production-schedules/99999');

        $response->assertStatus(404)
            ->assertJsonPath('code', ErrorCode::NOT_FOUND->value);
    }

    public function test_create_schedule_requires_valid_order(): void
    {
        $this->authAdmin();

        $response = $this->postJson('/api/v1/admin/production-schedules', [
            'order_id' => 99999,
            'schedule_date' => now()->addDay()->toDateString(),
            'process_name' => '印刷',
            'priority' => 2,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('code', ErrorCode::NOT_FOUND->value);
    }

    public function test_create_schedule_requires_schedule_date(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();

        $response = $this->postJson('/api/v1/admin/production-schedules', [
            'order_id' => $order->id,
            'process_name' => '印刷',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_create_schedule_requires_process_name(): void
    {
        $this->authAdmin();
        $order = Order::factory()->create();

        $response = $this->postJson('/api/v1/admin/production-schedules', [
            'order_id' => $order->id,
            'schedule_date' => now()->addDay()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_unauthenticated_cannot_access_schedules(): void
    {
        $this->getJson('/api/v1/admin/production-schedules')->assertUnauthorized();
    }
}
