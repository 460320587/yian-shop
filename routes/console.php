<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 自动取消超时未付款订单（每10分钟）
Schedule::command('orders:auto-cancel')->everyTenMinutes();

// 自动确认收货（每天凌晨2点）
Schedule::command('orders:auto-confirm-receipt')->dailyAt('02:00');

// 数据库备份（每天凌晨3点）
Schedule::command('db:backup --compress')->dailyAt('03:00');

// 清理过期备份（每周一凌晨4点）
Schedule::command('db:backup:cleanup')->weeklyOn(1, '04:00');

// 库存预警检查（每天上午8点）
Schedule::command('inventory:check-alert')->dailyAt('08:00');
