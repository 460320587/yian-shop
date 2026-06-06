<?php

declare(strict_types=1);

namespace App\Domains\Order\Queries;

use App\Domains\Order\Models\Order;
use App\Infrastructure\Queries\BaseQuery;
use Illuminate\Database\Eloquent\Builder;

class OrderQuery extends BaseQuery
{
    protected function query(): Builder
    {
        return Order::query();
    }

    protected function applyFilters(Builder $query): Builder
    {
        if (! empty($this->filters['order_no'])) {
            $query->where('order_no', 'like', '%' . $this->filters['order_no'] . '%');
        }

        if (isset($this->filters['status'])) {
            $query->where('status', (int) $this->filters['status']);
        }

        if (isset($this->filters['customer_id'])) {
            $query->where('customer_id', (int) $this->filters['customer_id']);
        }

        if (! empty($this->filters['min_amount'])) {
            $query->where('total_amount', '>=', (int) $this->filters['min_amount']);
        }

        if (! empty($this->filters['max_amount'])) {
            $query->where('total_amount', '<=', (int) $this->filters['max_amount']);
        }

        if (! empty($this->filters['created_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['created_from']);
        }

        if (! empty($this->filters['created_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['created_to']);
        }

        return $query;
    }
}
