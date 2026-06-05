<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AfterSaleApplied;
use App\Events\OrderCreated;
use App\Events\OrderDelivered;
use App\Events\PaymentSuccess;
use App\Listeners\AwardPointsOnPayment;
use App\Listeners\SendOrderCreatedNotification;
use App\Listeners\WritePaymentSuccessLog;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreated::class => [
            SendOrderCreatedNotification::class,
        ],
        PaymentSuccess::class => [
            WritePaymentSuccessLog::class,
            AwardPointsOnPayment::class,
        ],
        OrderDelivered::class => [
            // 可扩展：发送发货通知
        ],
        AfterSaleApplied::class => [
            // 可扩展：通知客服处理
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
