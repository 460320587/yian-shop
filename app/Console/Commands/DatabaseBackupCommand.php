<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup
                            {--compress : 使用 gzip 压缩备份文件}
                            {--prefix=yashop : 备份文件名前缀}';

    protected $description = '备份 MySQL 数据库到 storage/app/backups/db';

    public function handle(): int
    {
        $prefix = (string) $this->option('prefix');
        $compress = (bool) $this->option('compress');
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backups/db/{$prefix}_{$timestamp}.sql" . ($compress ? '.gz' : '');

        $this->info('开始数据库备份...');

        if (! Storage::disk('local')->makeDirectory('backups/db')) {
            $this->error('无法创建备份目录');

            return self::FAILURE;
        }

        try {
            $sql = $this->generateBackupSql();
        } catch (\Throwable $e) {
            $this->error('生成备份 SQL 失败: ' . $e->getMessage());

            return self::FAILURE;
        }

        $content = $compress ? gzencode($sql) : $sql;

        if ($content === false) {
            $this->error('压缩备份文件失败');

            return self::FAILURE;
        }

        Storage::disk('local')->put($filename, $content);

        $size = $this->formatBytes(strlen(Storage::disk('local')->get($filename) ?: ''));
        $this->info("数据库备份完成: {$filename} ({$size})");

        return self::SUCCESS;
    }

    /**
     * 生成数据库备份 SQL
     */
    private function generateBackupSql(): string
    {
        $sql = "-- Yashop Database Backup\n";
        $sql .= "-- Generated at: " . now()->toDateTimeString() . "\n";
        $sql .= "-- PHP: " . PHP_VERSION . " | Laravel: " . \Illuminate\Foundation\Application::VERSION . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $dbName = DB::getDatabaseName();
        $tables = DB::select('SHOW TABLES');
        $key = "Tables_in_{$dbName}";

        foreach ($tables as $table) {
            $tableName = $table->$key;

            $sql .= "-- ----------------------------\n";
            $sql .= "-- Table: {$tableName}\n";
            $sql .= "-- ----------------------------\n";

            $create = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sql .= $create[0]->{'Create Table'} . ";\n\n";

            $this->appendTableData($tableName, $sql);
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }

    /**
     * 追加表数据为 INSERT 语句
     */
    private function appendTableData(string $tableName, string &$sql): void
    {
        $columns = DB::getSchemaBuilder()->getColumnListing($tableName);

        if ($columns === []) {
            return;
        }

        $pageSize = 500;
        $offset = 0;
        $hasData = false;

        do {
            $rows = DB::table($tableName)
                ->offset($offset)
                ->limit($pageSize)
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            if (! $hasData) {
                $sql .= "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . '`) VALUES' . "\n";
                $hasData = true;
            } else {
                $sql .= ",\n";
            }

            $values = [];
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $escaped = array_map(function ($value) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    if (is_numeric($value) && ! is_string($value)) {
                        return $value;
                    }
                    if (is_string($value)) {
                        return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $value) . "'";
                    }

                    return "'" . (string) $value . "'";
                }, $rowArray);

                $values[] = '(' . implode(', ', $escaped) . ')';
            }

            $sql .= implode(",\n", $values);

            $offset += $pageSize;
        } while ($rows->count() === $pageSize);

        if ($hasData) {
            $sql .= ";\n\n";
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = (int) floor(log($bytes, 1024));
        $unitIndex = min($unitIndex, count($units) - 1);

        return round($bytes / (1024 ** $unitIndex), 2) . ' ' . $units[$unitIndex];
    }
}
