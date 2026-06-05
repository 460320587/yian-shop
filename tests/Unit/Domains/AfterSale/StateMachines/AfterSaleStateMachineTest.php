<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\AfterSale\StateMachines;

use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\AfterSale\StateMachines\AfterSaleStateMachine;
use App\Domains\Common\StateMachines\Exceptions\InvalidTransitionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AfterSaleStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private AfterSaleStateMachine $sm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sm = new AfterSaleStateMachine();
    }

    public function test_normal_approval_flow(): void
    {
        $this->assertTrue($this->sm->canTransition(1, 2)); // 待审核 → 审核通过
        $this->assertTrue($this->sm->canTransition(2, 4)); // 审核通过 → 处理中
        $this->assertTrue($this->sm->canTransition(4, 5)); // 处理中 → 已完成
    }

    public function test_reject_flow(): void
    {
        $this->assertTrue($this->sm->canTransition(1, 3)); // 待审核 → 审核拒绝
    }

    public function test_close_flow(): void
    {
        $this->assertTrue($this->sm->canTransition(1, 6)); // 待审核 → 已关闭
        $this->assertTrue($this->sm->canTransition(2, 6)); // 审核通过 → 已关闭
        $this->assertTrue($this->sm->canTransition(4, 6)); // 处理中 → 已关闭
    }

    public function test_invalid_transitions(): void
    {
        $this->assertFalse($this->sm->canTransition(5, 1)); // 已完成不能回退
        $this->assertFalse($this->sm->canTransition(3, 2)); // 审核拒绝不能通过
        $this->assertFalse($this->sm->canTransition(6, 1)); // 已关闭不能 reopen
    }

    public function test_transition_updates_status(): void
    {
        $afterSale = AfterSale::factory()->create(['status' => 1]);
        $this->sm->transition($afterSale, 2, ['operator_id' => 1, 'remark' => '审核通过']);

        $this->assertEquals(2, $afterSale->fresh()->status);
    }

    public function test_transition_sets_approved_amount_when_provided(): void
    {
        $afterSale = AfterSale::factory()->create(['status' => 1, 'approved_amount' => 0]);
        $this->sm->transition($afterSale, 2, ['approved_amount' => 5000]);

        $this->assertEquals(5000, $afterSale->fresh()->approved_amount->amount);
    }

    public function test_get_available_transitions_for_pending_review(): void
    {
        $available = $this->sm->getAvailableTransitions(1);
        $this->assertContains(2, $available);
        $this->assertContains(3, $available);
        $this->assertContains(6, $available);
    }
}
