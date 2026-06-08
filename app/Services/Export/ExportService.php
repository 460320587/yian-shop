<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Services\Export\Contracts\Exportable;

class ExportService
{
    /**
     * 将 Exportable 数据转换为 CSV 字符串
     */
    public function toCsv(Exportable $exportable): string
    {
        $handle = fopen('php://temp', 'r+');

        // Write BOM for UTF-8 Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");

        // Headers
        fputcsv($handle, $exportable->getHeaders());

        // Rows
        foreach ($exportable->toExportRows() as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * 流式输出 CSV 到浏览器
     */
    public function download(Exportable $exportable, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($exportable): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $exportable->getHeaders());

            foreach ($exportable->toExportRows() as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
