<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\User\Models\Customer;
use App\Events\PaymentSuccess;
use App\Infrastructure\Lock\LockManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PaymentWebhookConcurrentTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_holds_distributed_lock_during_processing(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $payment = Payment::factory()->create([
            'order_no' => $order->order_no,
            'status' => PaymentStatus::Pending->value,
            'amount' => 5000,
            'gateway' => 'wechat',
        ]);

        // 预先放置锁，模拟另一个进程正在处理同一支付单
        Cache::put('lock:webhook:wechat:' . $payment->payment_no, true, 60);

        $response = $this->postJson('/api/v1/webhooks/wechat-pay', [
            'out_trade_no' => $payment->payment_no,
            'transaction_id' => 'WX123456',
            'trade_state' => 'SUCCESS',
            'total_fee' => 5000,
        ]);

        // 当锁被占用时，应返回处理中（或排队等待后重试）
        // 当前实现返回 429 或特定错误码
        $response->assertStatus(429);

        // 确保支付单未被处理（锁持有期间不应处理）
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Pending->value,
        ]);

        Cache::forget('lock:webhook:wechat:' . $payment->payment_no);
    }

    public function test_concurrent_confirm_is_idempotent_via_atomic_update(): void
    {
        Event::fake([PaymentSuccess::class]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $payment = Payment::factory()->create([
            'order_no' => $order->order_no,
            'status' => PaymentStatus::Pending->value,
            'amount' => 5000,
            'gateway' => 'wechat',
        ]);

        // 模拟另一个进程已通过原子更新将状态改为 Success
        $payment->update(['status' => PaymentStatus::Success->value, 'paid_at' => now()]);

        // 当前进程再次调用 webhook
        $response = $this->postJson('/api/v1/webhooks/wechat-pay', [
            'out_trade_no' => $payment->payment_no,
            'transaction_id' => 'WX123456',
            'trade_state' => 'SUCCESS',
            'total_fee' => 5000,
        ]);

        // 应返回成功（幂等）
        $response->assertStatus(200)
            ->assertJson(['code' => 'SUCCESS']);

        // PaymentSuccess 事件不应再次派发
        Event::assertNotDispatched(PaymentSuccess::class);
    }

    public function test_recharge_webhook_is_idempotent_under_race_condition(): void
    {
        $customer = Customer::factory()->create(['balance' => 3000]);
        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'status' => PaymentStatus::Pending->value,
            'amount' => 5000,
            'gateway' => 'wechat',
            'order_no' => null,
        ]);

        // 模拟另一个进程已处理成功
        $payment->update(['status' => PaymentStatus::Success->value, 'paid_at' => now()]);
        $customer->update(['balance' => 8000]);

        // 当前进程再次调用
        $response = $this->postJson('/api/v1/webhooks/wechat-pay', [
            'out_trade_no' => $payment->payment_no,
            'transaction_id' => 'WX123456',
            'trade_state' => 'SUCCESS',
            'total_fee' => 5000,
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 'SUCCESS']);

        // 余额不应被重复增加
        $customer->refresh();
        $this->assertEquals(8000, $customer->balance->amount);
    }

    public function test_lock_is_released_after_webhook_processing(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $payment = Payment::factory()->create([
            'order_no' => $order->order_no,
            'status' => PaymentStatus::Pending->value,
            'amount' => 5000,
            'gateway' => 'wechat',
        ]);

        $this->postJson('/api/v1/webhooks/wechat-pay', [
            'out_trade_no' => $payment->payment_no,
            'transaction_id' => 'WX123456',
            'trade_state' => 'SUCCESS',
            'total_fee' => 5000,
        ]);

        // 处理完成后锁应被释放
        $lockKey = 'lock:webhook:wechat:' . $payment->payment_no;
        $this->assertFalse(Cache::has($lockKey), 'Webhook 处理完成后锁必须释放');
    }
}
