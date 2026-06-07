<?php

declare(strict_types=1);

namespace App\Domains\Sample\Queries;

use App\Domains\Sample\Models\SampleOrder;
use App\Infrastructure\Queries\BaseQuery;
use Illuminate\Database\Eloquent\Builder;

class SampleOrderQuery extends BaseQuery
{
    protected function query(): Builder
    {
        return SampleOrder::query();
    }

    protected function applyFilters(Builder $query): Builder
    {
        if (isset($this->filters['status'])) {
            $query->where('status', (int) $this->filters['status']);
        }

        if (! empty($this->filters['keyword'])) {
            $keyword = '%' . $this->filters['keyword'] . '%';
            $query->where(function (Builder $q) use ($keyword): void {
                $q->where('order_no', 'like', $keyword)
                    ->orWhereHas('customer', function (Builder $cq) use ($keyword): void {
                        $cq->where('phone', 'like', $keyword)
                            ->orWhere('nickname', 'like', $keyword);
                    });
            });
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
