<?php

declare(strict_types=1);

namespace App\Infrastructure\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

abstract class BaseCommand extends Command
{
    /**
     * 命令执行开始时间
     */
    protected ?Carbon $startedAt = null;

    /**
     * 执行命令
     */
    abstract public function handle(): int;

    /**
     * 记录命令开始日志
     */
    protected function logStart(): void
    {
        $this->startedAt = Carbon::now();
        $this->info(sprintf('[%s] 命令 %s 开始执行', $this->startedAt->toDateTimeString(), static::class));
    }

    /**
     * 记录命令结束日志
     */
    protected function logEnd(): void
    {
        $endedAt = Carbon::now();
        $duration = $this->startedAt ? $endedAt->diffInSeconds($this->startedAt) . 's' : 'N/A';
        $this->info(sprintf('[%s] 命令 %s 执行完成，耗时: %s', $endedAt->toDateTimeString(), static::class, $duration));
    }

    /**
     * 记录错误日志
     */
    protected function logError(\Throwable $e): void
    {
        $this->error(sprintf('[%s] 命令 %s 执行异常: %s', Carbon::now()->toDateTimeString(), static::class, $e->getMessage()));
    }
}
