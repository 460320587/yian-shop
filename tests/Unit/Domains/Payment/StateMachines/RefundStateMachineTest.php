<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\StateMachines;

use App\Domains\Admin\Models\Admin;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\StateMachines\RefundStateMachine;
use App\Domains\Common\StateMachines\Exceptions\InvalidTransitionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private RefundStateMachine $sm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sm = new RefundStateMachine();
    }

    public function test_approval_flow(): void
    {
        $this->assertTrue($this->sm->canTransition(0, 1)); // 待处理 → 审核通过
        $this->assertTrue($this->sm->canTransition(1, 3)); // 审核通过 → 处理中
        $this->assertTrue($this->sm->canTransition(3, 4)); // 处理中 → 已完成
    }

    public function test_reject_flow(): void
    {
        $this->assertTrue($this->sm->canTransition(0, 2)); // 待处理 → 审核拒绝
    }

    public function test_invalid_transitions(): void
    {
        $this->assertFalse($this->sm->canTransition(2, 1)); // 审核拒绝不能通过
        $this->assertFalse($this->sm->canTransition(4, 0)); // 已完成不能回退
        $this->assertFalse($this->sm->canTransition(1, 0)); // 审核通过不能回退
    }

    public function test_transition_updates_status(): void
    {
        $record = RefundRecord::factory()->create(['status' => 0]);
        $this->sm->transition($record, 1, ['approved_by' => null]);

        $this->assertEquals(1, $record->fresh()->status);
    }

    public function test_transition_sets_approved_fields(): void
    {
        $admin = Admin::factory()->create();
        $record = RefundRecord::factory()->create(['status' => 0]);
        $this->sm->transition($record, 1, ['approved_by' => $admin->id]);

        $fresh = $record->fresh();
        $this->assertEquals(1, $fresh->status);
        $this->assertEquals($admin->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    public function test_transition_sets_completed_at_on_completion(): void
    {
        $record = RefundRecord::factory()->create(['status' => 3]);
        $this->sm->transition($record, 4);

        $this->assertNotNull($record->fresh()->completed_at);
    }

    public function test_get_available_transitions_for_pending(): void
    {
        $available = $this->sm->getAvailableTransitions(0);
        $this->assertContains(1, $available);
        $this->assertContains(2, $available);
    }
}
