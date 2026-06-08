<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Payment\Enums\PaymentStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SystemReconcileCommand extends Command
{
    protected $signature = 'system:reconcile
                            {--skip-payment : 跳过订单与支付一致性检查}
                            {--skip-wallet : 跳过用户余额一致性检查}
                            {--skip-inventory : 跳过库存扣减一致性检查}';

    protected $description = '系统数据一致性巡检：订单/支付/库存/余额';

    /** @var array<int, array<string, mixed>> */
    private array $issues = [];

    public function handle(): int
    {
        $this->info('开始系统数据一致性巡检...');

        if (! $this->option('skip-payment')) {
            $this->checkOrderPaymentConsistency();
        }

        if (! $this->option('skip-wallet')) {
            $this->checkWalletBalance();
        }

        if (! $this->option('skip-inventory')) {
            $this->checkInventoryConsistency();
        }

        if ($this->issues === []) {
            $this->info('数据一致性检查完成，未发现异常。');

            return self::SUCCESS;
        }

        $this->warn("数据一致性检查完成，发现 " . count($this->issues) . " 处异常：");
        foreach ($this->issues as $issue) {
            $this->line("  [{$issue['type']}] {$issue['message']}");
            foreach ($issue['items'] as $item) {
                $this->line('    - ' . $item);
            }
        }

        return self::FAILURE;
    }

    private function checkOrderPaymentConsistency(): void
    {
        $paidOrderStatuses = [
            OrderStatus::Paid->value,
            OrderStatus::InProduction->value,
            OrderStatus::ProductionComplete->value,
            OrderStatus::PendingDelivery->value,
            OrderStatus::Shipped->value,
            OrderStatus::Completed->value,
        ];

        // 订单已支付/已发货/已完成，但支付单不是成功
        $mismatches1 = DB::table('orders')
            ->join('payments', 'orders.order_no', '=', 'payments.order_no')
            ->whereIn('orders.status', $paidOrderStatuses)
            ->where('payments.status', '!=', PaymentStatus::Success->value)
            ->select('orders.order_no', 'orders.status as order_status', 'payments.status as payment_status')
            ->limit(100)
            ->get();

        // 支付单已成功，但订单还是待支付
        $mismatches2 = DB::table('orders')
            ->join('payments', 'orders.order_no', '=', 'payments.order_no')
            ->where('orders.status', OrderStatus::PendingPayment->value)
            ->where('payments.status', PaymentStatus::Success->value)
            ->select('orders.order_no', 'orders.status as order_status', 'payments.status as payment_status')
            ->limit(100)
            ->get();

        $merged = $mismatches1->merge($mismatches2);
        if ($merged->isNotEmpty()) {
            $this->addIssue('订单与支付状态不一致', $merged->map(fn ($row) =>
                "订单 {$row->order_no}: 订单状态={$row->order_status}, 支付状态={$row->payment_status}"
            )->all());
        }
    }

    private function checkWalletBalance(): void
    {
        $mismatches = DB::select("
            SELECT c.id, c.balance, IFNULL(SUM(
                CASE 
                    WHEN wt.type = 1 THEN wt.amount
                    WHEN wt.type = 2 THEN -wt.amount
                    ELSE 0
                END
            ), 0) AS expected_balance
            FROM customers c
            LEFT JOIN wallet_transactions wt ON wt.customer_id = c.id
            GROUP BY c.id, c.balance
            HAVING c.balance != expected_balance
            LIMIT 100
        ");

        if ($mismatches !== []) {
            $this->addIssue('用户余额与流水不一致', array_map(fn ($row) =>
                "用户 ID={$row->id}: 当前余额={$row->balance}, 流水汇总={$row->expected_balance}",
                $mismatches
            ));
        }
    }

    private function checkInventoryConsistency(): void
    {
        // 已支付订单的商品，库存 locked_qty 应该 >= 订单项数量之和
        $mismatches = DB::select("
            SELECT 
                o.order_no,
                oi.product_id,
                SUM(oi.quantity) AS ordered_qty,
                i.locked_qty AS locked_qty
            FROM orders o
            INNER JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN inventories i ON i.product_id = oi.product_id
            WHERE o.status IN (?, ?, ?, ?, ?, ?)
            GROUP BY o.order_no, oi.product_id, i.locked_qty
            HAVING IFNULL(i.locked_qty, 0) < SUM(oi.quantity)
            LIMIT 100
        ", [
            OrderStatus::Paid->value,
            OrderStatus::InProduction->value,
            OrderStatus::ProductionComplete->value,
            OrderStatus::PendingDelivery->value,
            OrderStatus::Shipped->value,
            OrderStatus::Completed->value,
        ]);

        if ($mismatches !== []) {
            $this->addIssue('库存扣减不一致', array_map(fn ($row) =>
                "订单 {$row->order_no} 商品 ID={$row->product_id}: 下单数量={$row->ordered_qty}, 锁定库存={$row->locked_qty}",
                $mismatches
            ));
        }
    }

    /**
     * @param array<int, string> $items
     */
    private function addIssue(string $type, array $items): void
    {
        $this->issues[] = [
            'type' => $type,
            'message' => $type,
            'items' => $items,
        ];
    }
}
