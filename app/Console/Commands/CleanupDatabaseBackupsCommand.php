<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;

class CleanupDatabaseBackupsCommand extends Command
{
    protected $signature = 'db:backup:cleanup
                            {--keep-days=30 : 保留最近 N 天的备份}';

    protected $description = '清理过期的数据库备份文件';

    public function handle(): int
    {
        $keepDays = (int) $this->option('keep-days');

        if ($keepDays < 1) {
            $this->error('保留天数必须 >= 1');

            return self::FAILURE;
        }

        $cutoff = Carbon::now()->subDays($keepDays);
        $backupPath = Storage::disk('local')->path('backups/db');

        if (! is_dir($backupPath)) {
            $this->info('备份目录不存在，无需清理');

            return self::SUCCESS;
        }

        $finder = new Finder();
        $finder->files()
            ->in($backupPath)
            ->name('/\.(sql|sql\.gz)$/i')
            ->date('before ' . $cutoff->format('Y-m-d H:i:s'));

        $deleted = 0;
        foreach ($finder as $file) {
            $filepath = $file->getRealPath();
            if ($filepath === false) {
                continue;
            }
            if (@unlink($filepath)) {
                $this->line('已删除过期备份: ' . $file->getFilename());
                $deleted++;
            } else {
                $this->warn('删除失败: ' . $file->getFilename());
            }
        }

        $this->info("清理完成，共删除 {$deleted} 个过期备份");

        return self::SUCCESS;
    }
}
