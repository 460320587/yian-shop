<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AutoCancelOrderJob;
use Illuminate\Console\Command;

class AutoCancelOrdersCommand extends Command
{
    protected $signature = 'orders:auto-cancel';

    protected $description = '自动取消超时未付款订单（24小时）';

    public function handle(): int
    {
        $this->info('开始扫描超时未付款订单...');

        (new AutoCancelOrderJob())->handle();

        $this->info('超时订单自动取消完成。');

        return self::SUCCESS;
    }
}
