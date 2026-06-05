<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domains\Audit\Models\AuditLog;
use App\Events\PaymentSuccess;

class WritePaymentSuccessLog
{
    public function handle(PaymentSuccess $event): void
    {
        $payment = $event->payment;

        AuditLog::create([
            'admin_id' => $payment->customer_id,
            'action' => 'payment_success',
            'model_type' => 'payment',
            'model_id' => $payment->id,
            'remark' => "支付单 {$payment->payment_no} 支付成功，金额: " . $payment->amount->formatted(),
            'ip' => request()->ip(),
        ]);
    }
}
