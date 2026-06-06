<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * 查询对象基类
 *
 * Query 模式封装复杂数据查询，与 Actions 形成对称架构：
 * - Actions 负责"写"操作（命令）
 * - Queries 负责"读"操作（查询）
 *
 * 用法：
 *   $result = (new OrderQuery(['status' => 1, 'customer_id' => 5]))->paginate();
 *   $result = (new CustomerQuery(['keyword' => 'foo']))->get();
 */
abstract class BaseQuery
{
    /**
     * 查询条件数组
     */
    protected array $filters = [];

    /**
     * 每页条数
     */
    protected int $perPage = 15;

    /**
     * 排序字段
     */
    protected string $sortField = 'created_at';

    /**
     * 排序方向：asc / desc
     */
    protected string $sortDirection = 'desc';

    /**
     * 关联预加载
     */
    protected array $with = [];

    /**
     * @param array $filters 查询条件
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;

        if (isset($filters['per_page'])) {
            $this->perPage = max(1, (int) $filters['per_page']);
        }

        if (isset($filters['sort_field'])) {
            $this->sortField = (string) $filters['sort_field'];
        }

        if (isset($filters['sort_direction'])) {
            $direction = strtolower((string) $filters['sort_direction']);
            $this->sortDirection = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
        }
    }

    /**
     * 返回被查询的 Model 类名或 Builder 实例
     */
    abstract protected function query(): Builder;

    /**
     * 应用过滤条件
     */
    abstract protected function applyFilters(Builder $query): Builder;

    /**
     * 获取查询结果（不分页）
     */
    public function get(): Collection
    {
        $query = $this->buildQuery();

        return $query->get();
    }

    /**
     * 获取分页结果
     */
    public function paginate(): LengthAwarePaginator
    {
        $query = $this->buildQuery();

        return $query->paginate($this->perPage);
    }

    /**
     * 获取第一条结果
     */
    public function first(): ?Model
    {
        $query = $this->buildQuery();

        return $query->first();
    }

    /**
     * 统计数量
     */
    public function count(): int
    {
        $query = $this->buildQuery();

        return $query->count();
    }

    /**
     * 设置每页条数（链式调用）
     */
    public function perPage(int $perPage): static
    {
        $this->perPage = max(1, $perPage);

        return $this;
    }

    /**
     * 设置关联预加载（链式调用）
     */
    public function with(array $relations): static
    {
        $this->with = array_merge($this->with, $relations);

        return $this;
    }

    /**
     * 设置排序（链式调用）
     */
    public function orderBy(string $field, string $direction = 'desc'): static
    {
        $this->sortField = $field;
        $direction = strtolower($direction);
        $this->sortDirection = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        return $this;
    }

    /**
     * 构建最终查询 Builder
     */
    protected function buildQuery(): Builder
    {
        $query = $this->query();

        if (! empty($this->with)) {
            $query->with($this->with);
        }

        $query = $this->applyFilters($query);

        $query->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }
}
