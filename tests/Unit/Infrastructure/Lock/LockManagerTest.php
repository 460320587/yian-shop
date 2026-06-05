<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Lock;

use Tests\TestCase;
use App\Infrastructure\Lock\LockManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LockManagerTest extends TestCase
{
    public function test_lock_manager_exists(): void
    {
        $this->assertTrue(class_exists(LockManager::class), 'LockManager 类必须存在');
    }

    public function test_can_acquire_lock(): void
    {
        $manager = new LockManager();
        $acquired = $manager->acquire('test-lock-1', 10);

        $this->assertTrue($acquired, '必须能够获取锁');
        $this->assertTrue($manager->isLocked('test-lock-1'), '获取锁后 isLocked 必须返回 true');

        $manager->release('test-lock-1');
    }

    public function test_cannot_acquire_same_lock_twice(): void
    {
        $manager = new LockManager();
        $manager->acquire('test-lock-2', 10);

        $acquired = $manager->acquire('test-lock-2', 10);
        $this->assertFalse($acquired, '同一锁不能被重复获取');

        $manager->release('test-lock-2');
    }

    public function test_can_release_lock(): void
    {
        $manager = new LockManager();
        $manager->acquire('test-lock-3', 10);
        $manager->release('test-lock-3');

        $this->assertFalse($manager->isLocked('test-lock-3'), '释放后 isLocked 必须返回 false');
    }

    public function test_released_lock_can_be_reacquired(): void
    {
        $manager = new LockManager();
        $manager->acquire('test-lock-4', 10);
        $manager->release('test-lock-4');

        $acquired = $manager->acquire('test-lock-4', 10);
        $this->assertTrue($acquired, '释放后必须能重新获取');

        $manager->release('test-lock-4');
    }

    public function test_block_executes_callback_and_returns_result(): void
    {
        $manager = new LockManager();

        $result = $manager->block('test-lock-5', 10, function () {
            return 'success';
        });

        $this->assertEquals('success', $result, 'block 必须返回回调结果');
        $this->assertFalse($manager->isLocked('test-lock-5'), 'block 执行后必须自动释放锁');
    }

    public function test_block_releases_lock_even_on_exception(): void
    {
        $manager = new LockManager();

        try {
            $manager->block('test-lock-6', 10, function () {
                throw new \RuntimeException('test');
            });
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertFalse($manager->isLocked('test-lock-6'), '异常后必须自动释放锁');
    }

    public function test_lock_auto_expires(): void
    {
        $manager = new LockManager();
        $manager->acquire('test-lock-7', 1);
        $this->assertTrue($manager->isLocked('test-lock-7'));

        sleep(2);

        $this->assertFalse($manager->isLocked('test-lock-7'), '锁必须按 TTL 自动过期');
    }

    public function test_lock_supports_different_backends(): void
    {
        // 清理可能存在的遗留测试数据
        DB::table('cache_locks')->where('key', 'lock:test-lock-8')->delete();

        $manager = new LockManager('database');
        $acquired = $manager->acquire('test-lock-8', 10);

        $this->assertTrue($acquired, 'database 后端必须支持获取锁');
        $manager->release('test-lock-8');
    }
}
