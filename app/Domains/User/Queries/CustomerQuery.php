<?php

declare(strict_types=1);

namespace App\Domains\User\Queries;

use App\Domains\User\Models\Customer;
use App\Infrastructure\Queries\BaseQuery;
use Illuminate\Database\Eloquent\Builder;

class CustomerQuery extends BaseQuery
{
    protected function query(): Builder
    {
        return Customer::query();
    }

    protected function applyFilters(Builder $query): Builder
    {
        if (! empty($this->filters['keyword'])) {
            $keyword = $this->filters['keyword'];
            $query->where(function (Builder $q) use ($keyword): void {
                $q->where('phone', 'like', "%{$keyword}%")
                    ->orWhere('nickname', 'like', "%{$keyword}%");
            });
        }

        if (isset($this->filters['status'])) {
            $query->where('status', (int) $this->filters['status']);
        }

        if (isset($this->filters['vip_level'])) {
            $query->where('vip_level', (int) $this->filters['vip_level']);
        }

        return $query;
    }
}
