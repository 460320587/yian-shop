<?php

declare(strict_types=1);

namespace App\Domains\Enterprise\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterpriseAuth extends BaseModel
{
    protected $table = 'enterprise_auths';

    protected $fillable = [
        'customer_id',
        'company_name',
        'credit_code',
        'legal_person',
        'legal_person_id_card',
        'business_license_img',
        'contact_name',
        'contact_phone',
        'register_address',
        'office_address',
        'valid_date',
        'auth_status',
        'audit_remark',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'auth_status' => 'integer',
        'valid_date' => 'date:Y-m-d',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\User\Models\Customer::class);
    }
}
