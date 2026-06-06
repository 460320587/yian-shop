<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Payment\Models\RefundRecord;
use App\Infrastructure\Actions\BaseAction;
use Illuminate\Support\Str;

class ApplyRefundAction extends BaseAction
{
    public function __construct(
        private readonly int $customerId,
        private readonly array $data,
    ) {
    }

    public function handle(): RefundRecord
    {
        return RefundRecord::create([
            'order_id' => $this->data['order_id'],
            'payment_id' => $this->data['payment_id'],
            'customer_id' => $this->customerId,
            'refund_no' => $this->generateNo(),
            'amount' => $this->data['amount'],
            'reason' => $this->data['reason'],
            'status' => 0,
            'refund_path' => 'original',
        ]);
    }

    private function generateNo(): string
    {
        return 'R' . now()->format('Ymd') . strtoupper(Str::random(6));
    }
}
