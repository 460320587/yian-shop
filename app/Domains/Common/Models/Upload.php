<?php

declare(strict_types=1);

namespace App\Domains\Common\Models;

use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Upload extends BaseModel
{
    protected $fillable = [
        'user_id',
        'purpose',
        'original_name',
        'storage_path',
        'url',
        'file_size',
        'mime_type',
        'extension',
        'width',
        'height',
        'hash_md5',
        'is_virus_scanned',
        'virus_scan_result',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'is_virus_scanned' => 'integer',
        'status' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }
}
