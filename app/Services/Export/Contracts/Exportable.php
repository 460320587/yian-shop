<?php

declare(strict_types=1);

namespace App\Services\Export\Contracts;

interface Exportable
{
    /**
     * 返回 CSV 表头数组
     */
    public function getHeaders(): array;

    /**
     * 返回数据行生成器，每行是一个数组
     */
    public function toExportRows(): \Generator;
}
