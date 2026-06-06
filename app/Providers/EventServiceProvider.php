<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AfterSaleApplied;
use App\Events\OrderCreated;
use App\Events\OrderDelivered;
use App\Events\OrderStatusChanged;
use App\Events\PaymentSuccess;
use App\Listeners\AwardPointsOnPayment;
use App\Listeners\SendOrderCreatedNotification;
use App\Listeners\SendOrderDeliveredNotification;
use App\Listeners\SendOrderNotification;
use App\Listeners\WriteOrderStatusLog;
use App\Listeners\WritePaymentSuccessLog;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected static $shouldDiscoverEvents = false;

    protected $listen = [
        OrderCreated::class => [
            SendOrderCreatedNotification::class,
        ],
        PaymentSuccess::class => [
            WritePaymentSuccessLog::class,
            AwardPointsOnPayment::class,
        ],
        OrderStatusChanged::class => [
            WriteOrderStatusLog::class,
            SendOrderNotification::class,
        ],
        OrderDelivered::class => [
            SendOrderDeliveredNotification::class,
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
