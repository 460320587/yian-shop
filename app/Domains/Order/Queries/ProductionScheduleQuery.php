<?php

declare(strict_types=1);

namespace App\Domains\Order\Queries;

use App\Domains\Order\Models\ProductionSchedule;
use App\Infrastructure\Queries\BaseQuery;
use Illuminate\Database\Eloquent\Builder;

class ProductionScheduleQuery extends BaseQuery
{
    protected function query(): Builder
    {
        return ProductionSchedule::query();
    }

    protected function applyFilters(Builder $query): Builder
    {
        if (isset($this->filters['order_id'])) {
            $query->where('order_id', (int) $this->filters['order_id']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', (int) $this->filters['status']);
        }

        if (isset($this->filters['factory_id'])) {
            $query->where('factory_id', (int) $this->filters['factory_id']);
        }

        if (! empty($this->filters['schedule_date'])) {
            $query->whereDate('schedule_date', $this->filters['schedule_date']);
        }

        if (! empty($this->filters['schedule_date_from'])) {
            $query->whereDate('schedule_date', '>=', $this->filters['schedule_date_from']);
        }

        if (! empty($this->filters['schedule_date_to'])) {
            $query->whereDate('schedule_date', '<=', $this->filters['schedule_date_to']);
        }

        if (! empty($this->filters['process_name'])) {
            $query->where('process_name', 'like', '%' . $this->filters['process_name'] . '%');
        }

        return $query;
    }
}
