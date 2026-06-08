<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Services\Export\ExportService;
use App\Services\Export\Exporters\OrderExport;
use Illuminate\Http\JsonResponse;

class AdminExportController
{
    public function orders(): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $exporter = new OrderExport();
        $filename = 'orders_' . now()->format('Ymd_His') . '.csv';

        return (new ExportService())->download($exporter, $filename);
    }
}
