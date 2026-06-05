<?php

declare(strict_types=1);

namespace App\Domains\Order\Models;

use App\Domains\Common\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InkCoverageCheck extends BaseModel
{
    protected $table = 'ink_coverage_checks';

    protected $fillable = [
        'order_id',
        'file_id',
        'check_type',
        'ink_type',
        'coverage_c',
        'coverage_m',
        'coverage_y',
        'coverage_k',
        'total_coverage',
        'check_result',
        'check_report',
        'checked_by',
        'checked_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'file_id' => 'integer',
        'check_type' => 'integer',
        'coverage_c' => 'float',
        'coverage_m' => 'float',
        'coverage_y' => 'float',
        'coverage_k' => 'float',
        'total_coverage' => 'float',
        'check_result' => 'integer',
        'check_report' => 'array',
        'checked_by' => 'integer',
        'checked_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(OrderFile::class, 'file_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
