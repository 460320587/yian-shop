<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Infrastructure\Jobs\BaseJob;

class SendNotificationJob extends BaseJob
{
    public function __construct(
        private readonly int $customerId,
        private readonly string $title,
        private readonly string $content,
        private readonly ?string $actionUrl = null,
    ) {
    }

    public function handle(): void
    {
        \App\Domains\Notification\Models\CustomerNotification::create([
            'customer_id' => $this->customerId,
            'type' => 'system',
            'title' => $this->title,
            'content' => $this->content,
            'is_read' => 0,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionUrl ? '查看详情' : null,
        ]);
    }
}
