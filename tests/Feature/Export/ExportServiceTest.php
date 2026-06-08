<?php

declare(strict_types=1);

namespace Tests\Feature\Export;

use App\Services\Export\Contracts\Exportable;
use App\Services\Export\ExportService;
use Tests\TestCase;

class ExportServiceTest extends TestCase
{
    public function test_it_exports_csv_with_headers_and_rows(): void
    {
        $exporter = new class implements Exportable {
            public function getHeaders(): array
            {
                return ['ID', 'Name', 'Amount'];
            }

            public function toExportRows(): \Generator
            {
                yield [1, 'Alice', 100];
                yield [2, 'Bob', 200];
            }
        };

        $service = new ExportService();
        $csv = $service->toCsv($exporter);

        $lines = explode("\n", trim($csv));
        $this->assertCount(3, $lines); // header + 2 rows
        $this->assertSame("\xEF\xBB\xBF" . 'ID,Name,Amount', $lines[0]);
        $this->assertSame('1,Alice,100', $lines[1]);
        $this->assertSame('2,Bob,200', $lines[2]);
    }

    public function test_it_handles_empty_rows(): void
    {
        $exporter = new class implements Exportable {
            public function getHeaders(): array
            {
                return ['ID'];
            }

            public function toExportRows(): \Generator
            {
                return (function (): \Generator {
                    return;
                    yield;
                })();
            }
        };

        $service = new ExportService();
        $csv = $service->toCsv($exporter);

        $lines = explode("\n", trim($csv));
        $this->assertCount(1, $lines);
        $this->assertSame("\xEF\xBB\xBF" . 'ID', $lines[0]);
    }

    public function test_it_escapes_special_csv_characters(): void
    {
        $exporter = new class implements Exportable {
            public function getHeaders(): array
            {
                return ['Name', 'Description'];
            }

            public function toExportRows(): \Generator
            {
                yield ['Test, Inc.', 'Contains "quotes" and , comma'];
            }
        };

        $service = new ExportService();
        $csv = $service->toCsv($exporter);

        $lines = explode("\n", trim($csv));
        $this->assertStringContainsString('"Test, Inc."', $lines[1]);
        $this->assertStringContainsString('"Contains ""quotes"" and , comma"', $lines[1]);
    }
}
