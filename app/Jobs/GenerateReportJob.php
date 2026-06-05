<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Infrastructure\Jobs\BaseJob;

class GenerateReportJob extends BaseJob
{
    /**
     * 报表生成任务（示例）
     */
    public function __construct(
        private readonly string $reportType,
        private readonly array $filters,
        private readonly string $notifyEmail,
    ) {
    }

    public function handle(): void
    {
        // 模拟报表生成逻辑
        // 实际项目中会查询数据库、生成 Excel/PDF，然后发送邮件
        \Illuminate\Support\Facades\Log::info('报表生成完成', [
            'type' => $this->reportType,
            'filters' => $this->filters,
            'email' => $this->notifyEmail,
        ]);
    }
}
