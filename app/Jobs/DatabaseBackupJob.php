<?php

namespace App\Jobs;

use App\Models\BackupConfiguration;
use App\Services\GoogleDriveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class DatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 600;

    public function handle(): void
    {
        $config = BackupConfiguration::get();

        if (! $config->enabled) return;
        if (! $config->google_credentials || ! $config->google_folder_id) {
            Log::warning('DatabaseBackupJob: Google Drive not configured.');
            return;
        }

        $tmpDir = storage_path('app/backup_tmp_' . time());
        @mkdir($tmpDir, 0755, true);

        try {
            $tables = $this->resolveTables($config->tables ?? ['all']);
            $sqlPath = $tmpDir . '/dump.sql';

            $this->dumpTables($tables, $sqlPath);

            $zipPath = $tmpDir . '/backup.zip';
            $this->createZip($sqlPath, $zipPath);

            $drive    = GoogleDriveService::fromJson($config->google_credentials);
            $filename = 'orchestra_backup_' . now()->format('Y-m-d_His') . '.zip';
            $drive->uploadFile($zipPath, $config->google_folder_id, $filename);

            // Prune old backups
            if ($config->retention_days > 0) {
                $drive->pruneOldBackups($config->google_folder_id, $config->retention_days);
            }

            $config->update([
                'last_backup_at'      => now(),
                'last_backup_status'  => 'success',
                'last_backup_message' => "Backed up " . count($tables) . " tables to Drive as {$filename}",
            ]);

        } catch (\Throwable $e) {
            $config->update([
                'last_backup_at'      => now(),
                'last_backup_status'  => 'failed',
                'last_backup_message' => $e->getMessage(),
            ]);
            Log::error('DatabaseBackupJob failed: ' . $e->getMessage());
            throw $e;
        } finally {
            $this->cleanupDir($tmpDir);
        }
    }

    private function resolveTables(array $tableConfig): array
    {
        if (in_array('all', $tableConfig)) {
            return collect(DB::select('SHOW TABLES'))
                ->map(fn ($row) => array_values((array) $row)[0])
                ->toArray();
        }
        return $tableConfig;
    }

    private function dumpTables(array $tables, string $outPath): void
    {
        $pdo  = DB::connection()->getPdo();
        $sql  = "-- Orchestra ERP Backup\n-- Generated: " . now()->toIso8601String() . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Create table DDL
            $createRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $ddl       = $createRow['Create Table'] ?? ($createRow[array_key_last($createRow)] ?? '');
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n{$ddl};\n\n";

            // Row data
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (! empty($rows)) {
                $cols  = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $chunk = [];

                foreach ($rows as $row) {
                    $vals = array_map(function ($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, array_values($row));
                    $chunk[] = '(' . implode(', ', $vals) . ')';

                    if (count($chunk) >= 200) {
                        $sql .= "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $chunk) . ";\n";
                        $chunk = [];
                    }
                }

                if (! empty($chunk)) {
                    $sql .= "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $chunk) . ";\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($outPath, $sql);
    }

    private function createZip(string $sqlPath, string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException("Cannot create ZIP at {$zipPath}");
        }
        $zip->addFile($sqlPath, 'dump.sql');
        $zip->close();
    }

    private function cleanupDir(string $dir): void
    {
        foreach (glob("{$dir}/*") as $f) @unlink($f);
        @rmdir($dir);
    }
}
