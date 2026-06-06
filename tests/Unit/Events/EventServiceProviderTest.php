<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\AfterSaleApplied;
use App\Events\OrderCreated;
use App\Events\OrderDelivered;
use App\Events\OrderStatusChanged;
use App\Events\PaymentSuccess;
use App\Listeners\AwardPointsOnPayment;
use App\Listeners\SendOrderCreatedNotification;
use App\Listeners\SendOrderNotification;
use App\Listeners\WriteOrderStatusLog;
use App\Listeners\WritePaymentSuccessLog;
use App\Providers\EventServiceProvider;
use Tests\TestCase;

class EventServiceProviderTest extends TestCase
{
    public function test_order_created_has_listener(): void
    {
        $provider = new EventServiceProvider($this->app);
        $listen = (new \ReflectionClass($provider))->getProperty('listen');
        $listen->setAccessible(true);
        $map = $listen->getValue($provider);

        $this->assertArrayHasKey(OrderCreated::class, $map);
        $this->assertContains(SendOrderCreatedNotification::class, $map[OrderCreated::class]);
    }

    public function test_payment_success_has_listeners(): void
    {
        $provider = new EventServiceProvider($this->app);
        $listen = (new \ReflectionClass($provider))->getProperty('listen');
        $listen->setAccessible(true);
        $map = $listen->getValue($provider);

        $this->assertArrayHasKey(PaymentSuccess::class, $map);
        $this->assertContains(WritePaymentSuccessLog::class, $map[PaymentSuccess::class]);
        $this->assertContains(AwardPointsOnPayment::class, $map[PaymentSuccess::class]);
    }

    public function test_order_status_changed_has_listeners(): void
    {
        $provider = new EventServiceProvider($this->app);
        $listen = (new \ReflectionClass($provider))->getProperty('listen');
        $listen->setAccessible(true);
        $map = $listen->getValue($provider);

        $this->assertArrayHasKey(OrderStatusChanged::class, $map);
        $this->assertContains(WriteOrderStatusLog::class, $map[OrderStatusChanged::class]);
        $this->assertContains(SendOrderNotification::class, $map[OrderStatusChanged::class]);
    }

    public function test_order_delivered_has_listener(): void
    {
        $provider = new EventServiceProvider($this->app);
        $listen = (new \ReflectionClass($provider))->getProperty('listen');
        $listen->setAccessible(true);
        $map = $listen->getValue($provider);

        $this->assertArrayHasKey(OrderDelivered::class, $map);
    }

    public function test_after_sale_applied_has_listener(): void
    {
        $provider = new EventServiceProvider($this->app);
        $listen = (new \ReflectionClass($provider))->getProperty('listen');
        $listen->setAccessible(true);
        $map = $listen->getValue($provider);

        $this->assertArrayHasKey(AfterSaleApplied::class, $map);
    }
}
