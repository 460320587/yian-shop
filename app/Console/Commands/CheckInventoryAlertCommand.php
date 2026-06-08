<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Product\Models\Inventory;
use Illuminate\Console\Command;

class CheckInventoryAlertCommand extends Command
{
    protected $signature = 'inventory:check-alert
                            {--threshold-ratio=1.0 : 阈值倍数，默认 available_qty <= safety_stock * ratio}';

    protected $description = '检查库存预警：扫描低于安全库存的商品';

    public function handle(): int
    {
        $ratio = (float) $this->option('threshold-ratio');
        if ($ratio <= 0) {
            $this->error('threshold-ratio 必须大于 0');
            return self::FAILURE;
        }

        $this->info('开始检查库存预警...');

        $lowStockItems = Inventory::with('product:id,name,code')
            ->whereRaw('available_qty <= safety_stock * ?', [$ratio])
            ->orderBy('available_qty', 'asc')
            ->get();

        if ($lowStockItems->isEmpty()) {
            $this->info('库存检查完成，未发现低库存商品。');
            return self::SUCCESS;
        }

        $this->warn("发现 {$lowStockItems->count()} 个低库存商品：");
        $this->table(
            ['商品ID', '商品名称', '可用库存', '安全库存', '缺口'],
            $lowStockItems->map(fn (Inventory $item) => [
                $item->product_id,
                $item->product?->name ?? '-',
                $item->available_qty,
                $item->safety_stock,
                max(0, $item->safety_stock - $item->available_qty),
            ])->all()
        );

        return self::SUCCESS;
    }
}
