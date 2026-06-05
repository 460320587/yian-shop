<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class CustomerPayPassword extends BaseModel
{
    protected $table = 'customer_pay_passwords';

    protected $fillable = [
        'customer_id',
        'pay_password_hash',
        'fail_count',
        'locked_until',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'fail_count' => 'integer',
        'locked_until' => 'datetime:Y-m-d H:i:s',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function setPayPassword(string $password): void
    {
        $this->pay_password_hash = Hash::make($password);
        $this->fail_count = 0;
        $this->locked_until = null;
    }

    public function verify(string $password): bool
    {
        if ($this->isLocked()) {
            return false;
        }

        if (Hash::check($password, $this->pay_password_hash)) {
            $this->fail_count = 0;
            $this->save();
            return true;
        }

        $this->fail_count++;
        if ($this->fail_count >= 5) {
            $this->locked_until = now()->addHours(1);
        }
        $this->save();
        return false;
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
