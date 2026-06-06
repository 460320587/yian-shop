<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Payment\Models\WalletTransaction;
use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerWallet;
use App\Exceptions\BusinessException;
use App\Support\ErrorCode;

class WalletService
{
    private const MAX_RETRIES = 3;

    private array $typeMap = [
        'recharge' => 1,
        'consume' => 2,
        'refund' => 3,
        'withdraw' => 4,
        'freeze' => 5,
        'unfreeze' => 6,
    ];

    public function debit(
        Customer $customer,
        Money $amount,
        string $type,
        ?string $orderNo = null,
        ?string $paymentNo = null,
        ?string $remark = null,
    ): WalletTransaction {
        if ($amount->amount <= 0) {
            throw new BusinessException(ErrorCode::VALIDATION_ERROR, '扣款金额必须大于0');
        }

        $wallet = $this->getWallet($customer);

        if ($wallet->balance->amount < $amount->amount) {
            throw new BusinessException(ErrorCode::INSUFFICIENT_BALANCE, '余额不足');
        }

        $balanceBefore = $wallet->balance->amount;
        $balanceAfter = $balanceBefore - $amount->amount;

        $this->updateWithOptimisticLock($wallet, [
            'balance' => $balanceAfter,
            'total_consume' => $wallet->total_consume->amount + $amount->amount,
        ]);

        $this->syncCustomerBalance($customer, $balanceAfter);

        return $this->recordTransaction(
            $customer->id,
            $type,
            -$amount->amount,
            $balanceBefore,
            $balanceAfter,
            $orderNo,
            $paymentNo,
            $remark,
        );
    }

    public function credit(
        Customer $customer,
        Money $amount,
        string $type,
        ?string $paymentNo = null,
        ?string $remark = null,
    ): WalletTransaction {
        if ($amount->amount <= 0) {
            throw new BusinessException(ErrorCode::VALIDATION_ERROR, '充值金额必须大于0');
        }

        $wallet = $this->getWallet($customer);
        $balanceBefore = $wallet->balance->amount;
        $balanceAfter = $balanceBefore + $amount->amount;

        $extra = [];
        if ($type === 'recharge') {
            $extra['total_recharge'] = $wallet->total_recharge->amount + $amount->amount;
        }

        $this->updateWithOptimisticLock($wallet, array_merge([
            'balance' => $balanceAfter,
        ], $extra));

        $this->syncCustomerBalance($customer, $balanceAfter);

        return $this->recordTransaction(
            $customer->id,
            $type,
            $amount->amount,
            $balanceBefore,
            $balanceAfter,
            null,
            $paymentNo,
            $remark,
        );
    }

    private function getWallet(Customer $customer): CustomerWallet
    {
        $wallet = CustomerWallet::where('customer_id', $customer->id)->first();

        if ($wallet === null) {
            // 首次使用钱包时，从 customers.balance 同步初始余额，保持兼容
            $initialBalance = $customer->balance?->amount ?? 0;
            $wallet = CustomerWallet::create([
                'customer_id' => $customer->id,
                'balance' => $initialBalance,
                'frozen_amount' => 0,
                'total_recharge' => 0,
                'total_consume' => 0,
                'status' => 1,
                'version' => 0,
            ]);
        }

        return $wallet;
    }

    private function updateWithOptimisticLock(CustomerWallet $wallet, array $data): void
    {
        $retries = self::MAX_RETRIES;

        while ($retries-- > 0) {
            $freshWallet = CustomerWallet::where('id', $wallet->id)->first();

            if ($freshWallet === null) {
                throw new BusinessException(ErrorCode::SYSTEM_ERROR, '钱包记录不存在');
            }

            $affected = CustomerWallet::where('id', $freshWallet->id)
                ->where('version', $freshWallet->version)
                ->update(array_merge($data, [
                    'version' => $freshWallet->version + 1,
                ]));

            if ($affected > 0) {
                return;
            }
        }

        throw new BusinessException(ErrorCode::SYSTEM_ERROR, '钱包操作繁忙，请重试');
    }

    private function syncCustomerBalance(Customer $customer, int $newBalance): void
    {
        $affected = Customer::where('id', $customer->id)->update(['balance' => $newBalance]);
        if ($affected === 0) {
            throw new BusinessException(ErrorCode::SYSTEM_ERROR, '同步客户余额失败');
        }
    }

    private function recordTransaction(
        int $customerId,
        string $type,
        int $amount,
        int $balanceBefore,
        int $balanceAfter,
        ?string $orderNo,
        ?string $paymentNo,
        ?string $remark,
    ): WalletTransaction {
        return WalletTransaction::create([
            'customer_id' => $customerId,
            'type' => $this->typeMap[$type] ?? 0,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'order_no' => $orderNo,
            'payment_no' => $paymentNo,
            'remark' => $remark,
            'status' => 1,
        ]);
    }
}
