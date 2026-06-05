<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use App\Domains\Payment\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerWallet extends BaseModel
{
    protected $table = 'customer_wallets';

    protected $fillable = [
        'customer_id',
        'balance',
        'frozen_amount',
        'total_recharge',
        'total_consume',
        'status',
        'version',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'balance' => Money::class,
        'frozen_amount' => Money::class,
        'total_recharge' => Money::class,
        'total_consume' => Money::class,
        'status' => 'integer',
        'version' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'customer_id', 'customer_id');
    }

    public function availableBalance(): Money
    {
        return $this->balance->subtract($this->frozen_amount);
    }
}
