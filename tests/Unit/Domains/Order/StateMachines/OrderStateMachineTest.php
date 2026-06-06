<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\StateMachines;

use App\Domains\Order\Models\Order;
use App\Domains\Order\StateMachines\OrderStateMachine;
use App\Domains\Common\StateMachines\Exceptions\InvalidTransitionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private OrderStateMachine $sm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sm = new OrderStateMachine();
    }

    public function test_normal_flow_transitions(): void
    {
        // 正常正向流转
        $this->assertTrue($this->sm->canTransition(0, 1));   // 待提交 → 已提交
        $this->assertTrue($this->sm->canTransition(1, 11));  // 已提交 → 待付款
        $this->assertTrue($this->sm->canTransition(11, 12)); // 待付款 → 已付款
        $this->assertTrue($this->sm->canTransition(12, 13)); // 已付款 → 生产中
        $this->assertTrue($this->sm->canTransition(13, 15)); // 生产中 → 生产完成
        $this->assertTrue($this->sm->canTransition(15, 17)); // 生产完成 → 待发货
        $this->assertTrue($this->sm->canTransition(17, 20)); // 待发货 → 已发货
        $this->assertTrue($this->sm->canTransition(20, 54)); // 已发货 → 待收货
        $this->assertTrue($this->sm->canTransition(54, 55)); // 待收货 → 已收货
        $this->assertTrue($this->sm->canTransition(55, 60)); // 已收货 → 已完成
    }

    public function test_admin_shortcut_transitions(): void
    {
        // 管理后台快捷流转（兼容当前简化流程）
        $this->assertTrue($this->sm->canTransition(12, 20)); // 已付款 → 已发货（跳過生產環節）
        $this->assertTrue($this->sm->canTransition(20, 60)); // 已发货 → 已完成（跳过收货环节）
    }

    public function test_cancel_transitions(): void
    {
        $this->assertTrue($this->sm->canTransition(0, 61));  // 待提交 → 已取消
        $this->assertTrue($this->sm->canTransition(1, 61));  // 已提交 → 已取消
        $this->assertTrue($this->sm->canTransition(11, 61)); // 待付款 → 已取消
        $this->assertTrue($this->sm->canTransition(69, 61)); // 待复核 → 已取消
        $this->assertFalse($this->sm->canTransition(12, 61)); // 已付款不能取消
    }

    public function test_refund_transitions(): void
    {
        $this->assertTrue($this->sm->canTransition(12, 62)); // 已付款 → 退款中
        $this->assertTrue($this->sm->canTransition(20, 62)); // 已发货 → 退款中
        $this->assertTrue($this->sm->canTransition(55, 62)); // 已收货 → 退款中
        $this->assertTrue($this->sm->canTransition(60, 62)); // 已完成 → 退款中
        $this->assertTrue($this->sm->canTransition(62, 65)); // 退款中 → 已退款
        $this->assertTrue($this->sm->canTransition(62, 66)); // 退款中 → 已关闭
    }

    public function test_invalid_transitions_are_blocked(): void
    {
        $this->assertFalse($this->sm->canTransition(60, 12)); // 已完成不能回退到已付款
        $this->assertFalse($this->sm->canTransition(61, 11)); // 已取消不能恢复
        $this->assertFalse($this->sm->canTransition(65, 66)); // 已退款不能关闭
    }

    public function test_transition_executes_and_updates_status(): void
    {
        $order = Order::factory()->create(['status' => 11]);
        $this->sm->transition($order, 12);

        $this->assertEquals(12, $order->fresh()->status);
    }

    public function test_transition_records_status_log(): void
    {
        $order = Order::factory()->create(['status' => 11]);
        $this->sm->transition($order, 12, ['operator' => 'system']);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => 11,
            'to_status' => 12,
        ]);
    }

    public function test_transition_syncs_out_status_name(): void
    {
        $order = Order::factory()->create(['status' => 11, 'out_status_name' => '待付款']);
        $this->sm->transition($order, 12);

        $order->refresh();
        $this->assertEquals('已付款', $order->out_status_name);
    }

    public function test_transition_to_paid_records_paid_at(): void
    {
        $order = Order::factory()->create(['status' => 11, 'paid_at' => null]);
        $paidAt = now()->subMinute();
        $this->sm->transition($order, 12, ['paid_at' => $paidAt]);

        $order->refresh();
        $this->assertNotNull($order->paid_at);
    }

    public function test_get_available_transitions_for_pending_payment(): void
    {
        $available = $this->sm->getAvailableTransitions(11);
        $this->assertContains(12, $available); // 已付款
        $this->assertContains(61, $available); // 已取消
    }

    public function test_get_available_transitions_for_completed(): void
    {
        $available = $this->sm->getAvailableTransitions(60);
        $this->assertContains(62, $available); // 退款中
        $this->assertContains(100, $available); // 已归档
    }
}
