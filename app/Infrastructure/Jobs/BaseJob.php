<?php

declare(strict_types=1);

namespace App\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class BaseJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * 尝试次数
     */
    public int $tries = 3;

    /**
     * 最大异常次数（超过后不再重试）
     */
    public int $maxExceptions = 3;

    /**
     * 超时是否标记为失败
     */
    public bool $failOnTimeout = true;

    /**
     * 超时时间（秒）
     */
    public int $timeout = 60;

    /**
     * 执行队列任务
     */
    abstract public function handle(): void;
}
