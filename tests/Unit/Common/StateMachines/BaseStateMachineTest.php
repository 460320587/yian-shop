<?php

declare(strict_types=1);

namespace Tests\Unit\Common\StateMachines;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\StateMachines\BaseStateMachine;
use App\Domains\Common\StateMachines\Exceptions\InvalidTransitionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private function createStateMachine(array $transitionMap): BaseStateMachine
    {
        return new class($transitionMap) extends BaseStateMachine {
            public function __construct(private array $map) {}
            
            protected function transitions(): array { return $this->map; }
            protected function beforeTransition(Model $model, int $from, int $to, array $context): void {}
            protected function afterTransition(Model $model, int $from, int $to, array $context): void {}
        };
    }

    public function test_can_transition_returns_true_for_valid_transition(): void
    {
        $sm = $this->createStateMachine([10 => [20, 30]]);
        $this->assertTrue($sm->canTransition(10, 20));
        $this->assertTrue($sm->canTransition(10, 30));
    }

    public function test_can_transition_returns_false_for_invalid_transition(): void
    {
        $sm = $this->createStateMachine([10 => [20]]);
        $this->assertFalse($sm->canTransition(10, 30));
        $this->assertFalse($sm->canTransition(20, 10));
    }

    public function test_get_available_transitions_returns_targets(): void
    {
        $sm = $this->createStateMachine([10 => [20, 30], 20 => [40]]);
        $this->assertEquals([20, 30], $sm->getAvailableTransitions(10));
        $this->assertEquals([40], $sm->getAvailableTransitions(20));
        $this->assertEquals([], $sm->getAvailableTransitions(99));
    }

    public function test_transition_updates_model_status(): void
    {
        $sm = $this->createStateMachine([10 => [20]]);
        $model = new class extends BaseModel {
            protected $table = 'orders';
            protected $fillable = ['status'];
        };
        
        $order = \App\Domains\Order\Models\Order::factory()->create(['status' => 10]);
        $sm->transition($order, 20);

        $this->assertEquals(20, $order->fresh()->status);
    }

    public function test_transition_throws_exception_for_invalid_transition(): void
    {
        $this->expectException(InvalidTransitionException::class);

        $sm = $this->createStateMachine([10 => [20]]);
        $order = \App\Domains\Order\Models\Order::factory()->create(['status' => 10]);
        $sm->transition($order, 99);
    }

    public function test_transition_does_not_update_on_exception(): void
    {
        $sm = $this->createStateMachine([10 => [20]]);
        $order = \App\Domains\Order\Models\Order::factory()->create(['status' => 10]);

        try {
            $sm->transition($order, 99);
        } catch (InvalidTransitionException $e) {
            // expected
        }

        $this->assertEquals(10, $order->fresh()->status);
    }
}
