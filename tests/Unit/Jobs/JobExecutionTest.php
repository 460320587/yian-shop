<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Domains\Notification\Models\CustomerNotification;
use App\Domains\User\Models\Customer;
use App\Jobs\GenerateReportJob;
use App\Jobs\SendNotificationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class JobExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_notification_job_creates_notification(): void
    {
        $customer = Customer::factory()->create();
        $job = new SendNotificationJob(
            customerId: $customer->id,
            title: '测试通知',
            content: '这是测试内容',
            actionUrl: '/test',
        );

        $job->handle();

        $this->assertDatabaseHas('customer_notifications', [
            'customer_id' => $customer->id,
            'title' => '测试通知',
            'type' => 'system',
        ]);
    }

    public function test_generate_report_job_logs_completion(): void
    {
        Log::spy();

        $job = new GenerateReportJob(
            reportType: 'daily_order',
            filters: ['date' => '2026-06-01'],
            notifyEmail: 'admin@example.com',
        );

        $job->handle();

        Log::shouldHaveReceived('info')->once();
    }

    public function test_jobs_extend_base_job(): void
    {
        $this->assertTrue(
            is_subclass_of(SendNotificationJob::class, \App\Infrastructure\Jobs\BaseJob::class),
            'SendNotificationJob 必须继承 BaseJob'
        );
        $this->assertTrue(
            is_subclass_of(GenerateReportJob::class, \App\Infrastructure\Jobs\BaseJob::class),
            'GenerateReportJob 必须继承 BaseJob'
        );
    }
}
