<?php

declare(strict_types=1);

namespace App\Domains\Common\Models;

use App\Domains\Common\Casts\MoneyCast;
use App\Domains\Common\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

abstract class BaseModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The primary key type. Override in child class to 'string' for UUID.
     */
    protected string $primaryKeyType = 'int';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope a query to only include active records.
     * Assumes a `status` column where 1 means active.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include recent records.
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Determine if the model uses unique IDs (UUID).
     */
    public function usesUniqueIds(): bool
    {
        return $this->primaryKeyType === 'string';
    }

    /**
     * Get the casts array.
     *
     * @return array<string, string>
     */
    public function getCasts(): array
    {
        $casts = parent::getCasts();

        // Register custom casts
        foreach ($casts as $key => $cast) {
            if ($cast === Money::class) {
                $casts[$key] = MoneyCast::class;
            }
        }

        return $casts;
    }

    /**
     * Serialize date to the configured format.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format($this->dateFormat);
    }
}
