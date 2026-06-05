<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Actions;

use Tests\TestCase;
use App\Infrastructure\Actions\BaseAction;
use Illuminate\Support\Facades\DB;

class BaseActionTest extends TestCase
{
    public function test_base_action_is_abstract(): void
    {
        $reflection = new \ReflectionClass(BaseAction::class);
        $this->assertTrue($reflection->isAbstract(), 'BaseAction 必须是抽象类');
    }

    public function test_base_action_has_handle_method(): void
    {
        $reflection = new \ReflectionClass(BaseAction::class);
        $this->assertTrue($reflection->hasMethod('handle'), 'BaseAction 必须定义 handle() 方法');

        $method = $reflection->getMethod('handle');
        $this->assertTrue($method->isAbstract(), 'handle() 必须是抽象方法');
    }

    public function test_base_action_provides_transaction_helper(): void
    {
        $reflection = new \ReflectionClass(BaseAction::class);
        $this->assertTrue($reflection->hasMethod('transaction'), 'BaseAction 必须提供 transaction() 辅助方法');

        $method = $reflection->getMethod('transaction');
        $this->assertTrue($method->isProtected(), 'transaction() 必须是 protected');
    }

    public function test_transaction_helper_executes_callback_in_database_transaction(): void
    {
        $captured = ['called' => false, 'insideTransaction' => false];

        $action = new class($captured) extends BaseAction {
            private array $captured;

            public function __construct(array &$captured)
            {
                $this->captured = &$captured;
            }

            public function execute(): void
            {
                $this->transaction(function () {
                    $this->captured['called'] = true;
                    $this->captured['insideTransaction'] = DB::transactionLevel() > 0;
                });
            }

            public function handle(): mixed { return null; }
        };

        $action->execute();

        $this->assertTrue($captured['called'], 'transaction 回调必须被执行');
        $this->assertTrue($captured['insideTransaction'], '回调必须在数据库事务中执行');
    }

    public function test_transaction_helper_rethrows_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('test error');

        $action = new class extends BaseAction {
            public function execute(): void
            {
                $this->transaction(function () {
                    throw new \RuntimeException('test error');
                });
            }

            public function handle(): mixed { return null; }
        };

        $action->execute();
    }

    public function test_transaction_rolls_back_on_exception(): void
    {
        $action = new class extends BaseAction {
            public function execute(): void
            {
                try {
                    $this->transaction(function () {
                        DB::table('customers')->insert(['phone' => '13800138000', 'nickname' => 'test_tx']);
                        throw new \RuntimeException('rollback');
                    });
                } catch (\RuntimeException $e) {
                    // expected
                }
            }

            public function handle(): mixed { return null; }
        };

        $action->execute();

        $this->assertDatabaseMissing('customers', ['phone' => '13800138000']);
    }

    public function test_base_action_provides_validate_method(): void
    {
        $reflection = new \ReflectionClass(BaseAction::class);
        $this->assertTrue($reflection->hasMethod('validate'), 'BaseAction 必须提供 validate() 辅助方法');
    }
}
