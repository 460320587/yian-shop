<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AutoConfirmReceiptJob;
use Illuminate\Console\Command;

class AutoConfirmReceiptCommand extends Command
{
    protected $signature = 'orders:auto-confirm-receipt';

    protected $description = '自动确认收货：发货后超过7天未确认的订单自动完成';

    public function handle(): int
    {
        $this->info('开始扫描超时未确认收货的订单...');

        (new AutoConfirmReceiptJob())->handle();

        $this->info('超时订单自动确认收货完成。');

        return self::SUCCESS;
    }
}
