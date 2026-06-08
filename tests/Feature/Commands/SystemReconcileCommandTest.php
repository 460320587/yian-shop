<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\WalletTransaction;
use App\Domains\Product\Models\Inventory;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemReconcileCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconcile_reports_no_issues_for_consistent_data(): void
    {
        $customer = Customer::factory()->create(['balance' => 5000]);
        WalletTransaction::factory()->create([
            'customer_id' => $customer->id,
            'type' => 1, // recharge
            'amount' => 5000,
            'balance_before' => 0,
            'balance_after' => 5000,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::Paid->value,
            'total_amount' => 3000,
            'customer_id' => $customer->id,
        ]);

        Payment::factory()->create([
            'order_no' => $order->order_no,
            'status' => PaymentStatus::Success->value,
            'amount' => 3000,
            'customer_id' => $customer->id,
        ]);

        $product = Product::factory()->create();
        Inventory::factory()->create([
            'product_id' => $product->id,
            'available_qty' => 980,
            'reserved_qty' => 0,
            'locked_qty' => 20,
        ]);

        $this->artisan('system:reconcile')
            ->assertSuccessful()
            ->expectsOutputToContain('数据一致性检查完成')
            ->execute();
    }

    public function test_reconcile_detects_order_payment_mismatch(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Paid->value,
            'total_amount' => 3000,
        ]);

        Payment::factory()->create([
            'order_no' => $order->order_no,
            'status' => PaymentStatus::Pending->value, // 不一致
            'amount' => 3000,
            'customer_id' => $order->customer_id,
        ]);

        $this->artisan('system:reconcile')
            ->assertFailed()
            ->expectsOutputToContain('订单与支付状态不一致')
            ->execute();
    }

    public function test_reconcile_detects_payment_without_paid_order(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PendingPayment->value, // 未支付
            'total_amount' => 3000,
        ]);

        Payment::factory()->create([
            'order_no' => $order->order_no,
            'status' => PaymentStatus::Success->value, // 已支付
            'amount' => 3000,
            'customer_id' => $order->customer_id,
        ]);

        $this->artisan('system:reconcile')
            ->assertFailed()
            ->expectsOutputToContain('订单与支付状态不一致')
            ->execute();
    }

    public function test_reconcile_detects_wallet_balance_mismatch(): void
    {
        $customer = Customer::factory()->create(['balance' => 8000]);
        WalletTransaction::factory()->create([
            'customer_id' => $customer->id,
            'type' => 1, // recharge
            'amount' => 5000,
            'balance_before' => 0,
            'balance_after' => 5000,
        ]);

        $this->artisan('system:reconcile')
            ->assertFailed()
            ->expectsOutputToContain('用户余额与流水不一致')
            ->execute();
    }

    public function test_reconcile_detects_inventory_mismatch_for_paid_order(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Paid->value,
            'total_amount' => 3000,
        ]);

        Payment::factory()->create([
            'order_no' => $order->order_no,
            'status' => PaymentStatus::Success->value,
            'amount' => 3000,
            'customer_id' => $order->customer_id,
        ]);

        $product = Product::factory()->create();
        Inventory::factory()->create([
            'product_id' => $product->id,
            'available_qty' => 1000,
            'reserved_qty' => 0,
            'locked_qty' => 0, // 应该已经被扣减
        ]);

        // 创建订单项引用该产品
        \App\Domains\Order\Models\OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'unit_price' => 150,
            'total_price' => 3000,
        ]);

        $this->artisan('system:reconcile')
            ->assertFailed()
            ->expectsOutputToContain('库存扣减不一致')
            ->execute();
    }

    public function test_reconcile_respects_skip_options(): void
    {
        $customer = Customer::factory()->create(['balance' => 8000]);
        WalletTransaction::factory()->create([
            'customer_id' => $customer->id,
            'type' => 1,
            'amount' => 5000,
            'balance_before' => 0,
            'balance_after' => 5000,
        ]);

        $this->artisan('system:reconcile', ['--skip-wallet' => true])
            ->assertSuccessful()
            ->execute();
    }
}
