<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseMaintenanceService
{
    // ── SQL File Parsing ───────────────────────────────────────────────────────

    public function parseSqlFile(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Cannot read the uploaded file.');
        }

        // Strip line comments (-- ...)
        $content = preg_replace('/--[^\n]*/', '', $content);
        // Strip block comments (/* ... */)
        $content = preg_replace('/\/\*[\s\S]*?\*\//', '', $content);

        $statements = $this->splitStatements($content);
        $parsed = [];

        foreach ($statements as $sql) {
            $sql = trim($sql);
            if (empty($sql)) continue;

            $upper = strtoupper(ltrim($sql));
            $type  = $this->detectType($upper);
            $table = $this->extractTable($sql, $type);

            $parsed[] = [
                'id'      => uniqid('stmt_', true),
                'type'    => $type,
                'table'   => $table ?: '—',
                'sql'     => $sql,
                'preview' => Str::limit($sql, 160),
                'safe'    => !in_array($type, ['DROP_TABLE', 'DROP_DATABASE', 'TRUNCATE', 'DELETE', 'UNKNOWN']),
            ];
        }

        return $parsed;
    }

    private function splitStatements(string $sql): array
    {
        $statements = [];
        $current    = '';
        $inString   = false;
        $stringChar = '';
        $len        = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            if ($inString) {
                $current .= $char;
                if ($char === $stringChar && ($i === 0 || $sql[$i - 1] !== '\\')) {
                    $inString = false;
                }
            } elseif ($char === '"' || $char === "'") {
                $inString   = true;
                $stringChar = $char;
                $current   .= $char;
            } elseif ($char === ';') {
                $stmt = trim($current);
                if (!empty($stmt)) $statements[] = $stmt;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (!empty(trim($current))) {
            $statements[] = trim($current);
        }

        return $statements;
    }

    private function detectType(string $upper): string
    {
        if (str_starts_with($upper, 'CREATE TABLE'))    return 'CREATE_TABLE';
        if (str_starts_with($upper, 'CREATE INDEX'))    return 'CREATE_INDEX';
        if (str_starts_with($upper, 'CREATE UNIQUE'))   return 'CREATE_INDEX';
        if (str_starts_with($upper, 'ALTER TABLE'))     return 'ALTER_TABLE';
        if (str_starts_with($upper, 'DROP TABLE'))      return 'DROP_TABLE';
        if (str_starts_with($upper, 'DROP DATABASE'))   return 'DROP_DATABASE';
        if (str_starts_with($upper, 'TRUNCATE'))        return 'TRUNCATE';
        if (str_starts_with($upper, 'INSERT'))          return 'INSERT';
        if (str_starts_with($upper, 'UPDATE'))          return 'UPDATE';
        if (str_starts_with($upper, 'DELETE'))          return 'DELETE';
        if (str_starts_with($upper, 'CREATE DATABASE')) return 'CREATE_DATABASE';
        if (str_starts_with($upper, 'USE '))            return 'USE_DB';
        if (str_starts_with($upper, 'SET '))            return 'SET';
        if (str_starts_with($upper, 'LOCK '))           return 'LOCK';
        if (str_starts_with($upper, 'UNLOCK '))         return 'UNLOCK';
        return 'UNKNOWN';
    }

    private function extractTable(string $sql, string $type): string
    {
        $patterns = [
            'CREATE_TABLE' => '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?(\w+)[`"]?/i',
            'ALTER_TABLE'  => '/ALTER\s+TABLE\s+[`"]?(\w+)[`"]?/i',
            'DROP_TABLE'   => '/DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?[`"]?(\w+)[`"]?/i',
            'TRUNCATE'     => '/TRUNCATE\s+(?:TABLE\s+)?[`"]?(\w+)[`"]?/i',
            'INSERT'       => '/INSERT\s+(?:IGNORE\s+)?INTO\s+[`"]?(\w+)[`"]?/i',
            'UPDATE'       => '/UPDATE\s+[`"]?(\w+)[`"]?/i',
            'DELETE'       => '/DELETE\s+FROM\s+[`"]?(\w+)[`"]?/i',
            'CREATE_INDEX' => '/ON\s+[`"]?(\w+)[`"]?/i',
        ];

        $pattern = $patterns[$type] ?? null;
        if ($pattern && preg_match($pattern, $sql, $m)) {
            return $m[1];
        }
        return '';
    }

    public function executeStatements(array $statements): array
    {
        $results = [];
        foreach ($statements as $stmt) {
            try {
                DB::unprepared($stmt['sql']);
                $results[] = [
                    'table'   => $stmt['table'],
                    'type'    => $stmt['type'],
                    'preview' => $stmt['preview'],
                    'status'  => 'success',
                    'message' => 'Executed successfully',
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'table'   => $stmt['table'],
                    'type'    => $stmt['type'],
                    'preview' => $stmt['preview'],
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }
        return $results;
    }

    // ── CSV / Excel Import ─────────────────────────────────────────────────────

    public function parseCsvFile(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) throw new \RuntimeException('Cannot open the uploaded CSV file.');

        // Detect delimiter from first line
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = substr_count($firstLine, ',') >= substr_count($firstLine, ';') ? ',' : ';';

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            throw new \RuntimeException('Empty or invalid CSV file.');
        }

        $headers = array_map('trim', $headers);
        $preview = [];
        $count   = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false && $count < 5) {
            if (count($row) >= count($headers)) {
                $preview[] = array_combine(
                    $headers,
                    array_slice(array_map('trim', $row), 0, count($headers))
                );
            }
            $count++;
        }

        $totalRows = $count;
        while (fgetcsv($handle, 0, $delimiter) !== false) {
            $totalRows++;
        }

        fclose($handle);

        return [
            'headers'    => $headers,
            'preview'    => $preview,
            'total_rows' => $totalRows,
            'delimiter'  => $delimiter,
        ];
    }

    public function parseExcelFile(string $path): array
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException(
                'PhpSpreadsheet is not installed. Run: composer require maatwebsite/excel'
            );
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, false);

        if (empty($rows)) throw new \RuntimeException('Empty spreadsheet.');

        $headers = array_map('trim', array_shift($rows));
        $preview = array_slice($rows, 0, 5);
        $preview = array_map(fn($r) => array_combine($headers, array_slice($r, 0, count($headers))), $preview);

        return [
            'headers'    => $headers,
            'preview'    => $preview,
            'total_rows' => count($rows),
            'delimiter'  => null,
        ];
    }

    public function importCsvFile(string $path, string $table, string $strategy, array $columnMap, ?string $delimiter = null): array
    {
        if (!Schema::hasTable($table)) {
            throw new \RuntimeException("Table `{$table}` does not exist in the database.");
        }

        $handle    = fopen($path, 'r');
        $firstLine = fgets($handle);
        rewind($handle);
        $delim   = $delimiter ?? (substr_count($firstLine, ',') >= substr_count($firstLine, ';') ? ',' : ';');
        $headers = array_map('trim', fgetcsv($handle, 0, $delim));

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = 0;
        $errorMessages = [];
        $batch    = [];

        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            if (count($row) < count($headers)) { $skipped++; continue; }

            $data   = array_combine($headers, array_slice(array_map('trim', $row), 0, count($headers)));
            $mapped = [];

            foreach ($columnMap as $csvCol => $dbCol) {
                if (!empty($dbCol) && array_key_exists($csvCol, $data)) {
                    $mapped[$dbCol] = $data[$csvCol] === '' ? null : $data[$csvCol];
                }
            }

            if (empty($mapped)) { $skipped++; continue; }
            $batch[] = $mapped;

            if (count($batch) >= 200) {
                [$ins, $upd, $err, $msgs] = $this->flushBatch($batch, $table, $strategy, count($errorMessages));
                $inserted += $ins; $updated += $upd; $errors += $err;
                $errorMessages = array_merge($errorMessages, $msgs);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            [$ins, $upd, $err, $msgs] = $this->flushBatch($batch, $table, $strategy, count($errorMessages));
            $inserted += $ins; $updated += $upd; $errors += $err;
            $errorMessages = array_merge($errorMessages, $msgs);
        }

        fclose($handle);

        return compact('inserted', 'updated', 'skipped', 'errors', 'errorMessages');
    }

    private function flushBatch(array $batch, string $table, string $strategy, int $existingErrCount): array
    {
        $inserted = 0; $updated = 0; $errors = 0; $msgs = [];

        try {
            if ($strategy === 'insert') {
                DB::table($table)->insert($batch);
                $inserted = count($batch);
            } elseif ($strategy === 'ignore') {
                DB::table($table)->insertOrIgnore($batch);
                $inserted = count($batch);
            } elseif ($strategy === 'upsert') {
                $firstRow  = $batch[0];
                $uniqueBy  = array_key_exists('id', $firstRow) ? ['id'] : [array_key_first($firstRow)];
                $updateCols = array_keys($firstRow);
                DB::table($table)->upsert($batch, $uniqueBy, $updateCols);
                $updated = count($batch);
            }
        } catch (\Throwable $e) {
            foreach ($batch as $row) {
                try {
                    if ($strategy === 'upsert' && isset($row['id'])) {
                        DB::table($table)->updateOrInsert(['id' => $row['id']], $row);
                        $updated++;
                    } else {
                        DB::table($table)->insertOrIgnore($row);
                        $inserted++;
                    }
                } catch (\Throwable $rowErr) {
                    $errors++;
                    if ($existingErrCount + count($msgs) < 5) {
                        $msgs[] = $rowErr->getMessage();
                    }
                }
            }
        }

        return [$inserted, $updated, $errors, $msgs];
    }

    // ── Schema Comparison ──────────────────────────────────────────────────────

    public function getCurrentSchema(): array
    {
        $tables = DB::select('SHOW TABLES');
        $schema = [];

        foreach ($tables as $tableRow) {
            $tableName = array_values((array) $tableRow)[0];
            try {
                $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
                $schema[$tableName] = collect($columns)->map(fn($c) => [
                    'type'    => $c->Type,
                    'null'    => $c->Null,
                    'default' => $c->Default ?? '',
                    'extra'   => $c->Extra,
                ])->keyBy('Field')->toArray();
            } catch (\Throwable) {}
        }

        return $schema;
    }

    public function compareSchemaFromSql(array $createStatements): array
    {
        $current = $this->getCurrentSchema();
        $diff    = [];

        $requiredTables = [];

        foreach ($createStatements as $stmt) {
            if ($stmt['type'] !== 'CREATE_TABLE') continue;
            $table            = $stmt['table'];
            $requiredTables[] = $table;

            if (!isset($current[$table])) {
                $diff[] = [
                    'type'        => 'missing_table',
                    'table'       => $table,
                    'description' => 'Table does not exist — needs to be created',
                    'severity'    => 'critical',
                    'action_sql'  => $stmt['sql'],
                    'columns'     => [],
                ];
            } else {
                $reqCols = $this->extractColumnsFromCreate($stmt['sql']);
                $missing = array_diff_key($reqCols, $current[$table]);

                if (!empty($missing)) {
                    $clauses = [];
                    foreach ($missing as $col => $def) {
                        $clauses[] = "ADD COLUMN `{$col}` {$def}";
                    }
                    $diff[] = [
                        'type'        => 'missing_columns',
                        'table'       => $table,
                        'description' => count($missing) . ' missing column(s): ' . implode(', ', array_keys($missing)),
                        'severity'    => 'warning',
                        'action_sql'  => "ALTER TABLE `{$table}` " . implode(', ', $clauses),
                        'columns'     => array_keys($missing),
                    ];
                } else {
                    $diff[] = [
                        'type'        => 'ok',
                        'table'       => $table,
                        'description' => 'Table structure matches',
                        'severity'    => 'success',
                        'action_sql'  => null,
                        'columns'     => [],
                    ];
                }
            }
        }

        // Extra tables present in DB but not in the uploaded SQL
        foreach (array_keys($current) as $tableName) {
            if (!in_array($tableName, $requiredTables) && $tableName !== 'migrations') {
                $diff[] = [
                    'type'        => 'extra_table',
                    'table'       => $tableName,
                    'description' => 'Exists in DB but not in uploaded SQL (safe to keep)',
                    'severity'    => 'info',
                    'action_sql'  => null,
                    'columns'     => [],
                ];
            }
        }

        return $diff;
    }

    private function extractColumnsFromCreate(string $sql): array
    {
        $cols  = [];
        $start = strpos($sql, '(');
        $end   = strrpos($sql, ')');
        if ($start === false || $end === false) return $cols;

        $body  = substr($sql, $start + 1, $end - $start - 1);
        $parts = [];
        $depth = 0;
        $cur   = '';

        foreach (str_split($body) as $char) {
            if ($char === '(')                   { $depth++; $cur .= $char; }
            elseif ($char === ')')               { $depth--; $cur .= $char; }
            elseif ($char === ',' && $depth === 0) { $parts[] = trim($cur); $cur = ''; }
            else                                 { $cur .= $char; }
        }
        if (!empty(trim($cur))) $parts[] = trim($cur);

        $skip = ['PRIMARY KEY', 'KEY ', 'INDEX ', 'UNIQUE KEY', 'UNIQUE INDEX', 'CONSTRAINT', 'FULLTEXT'];

        foreach ($parts as $part) {
            $part  = trim($part);
            $upper = strtoupper($part);
            foreach ($skip as $s) {
                if (str_starts_with($upper, $s)) continue 2;
            }
            if (preg_match('/^[`"]?(\w+)[`"]?\s+(\S.*)/s', $part, $m)) {
                $cols[$m[1]] = trim($m[2]);
            }
        }

        return $cols;
    }

    public function compareSchemaFromMigrations(): array
    {
        $current = $this->getCurrentSchema();
        $pending = $this->getPendingMigrations();
        $diff    = [];

        if (empty($pending)) {
            return [['type' => 'all_up_to_date', 'table' => '—', 'description' => 'No pending migrations. Database is up to date.', 'severity' => 'success', 'action_sql' => null]];
        }

        foreach ($pending as $migration) {
            $content = file_get_contents($migration['file']);

            preg_match_all("/Schema::create\s*\(\s*['\"](\w+)['\"]/", $content, $creates);
            foreach ($creates[1] as $table) {
                $exists = isset($current[$table]);
                $diff[] = [
                    'type'        => $exists ? 'table_conflict' : 'will_create',
                    'migration'   => $migration['name'],
                    'table'       => $table,
                    'description' => $exists
                        ? 'Migration will create this table but it already exists'
                        : 'Migration will create this table',
                    'severity'    => $exists ? 'warning' : 'info',
                    'action_sql'  => null,
                ];
            }

            preg_match_all("/Schema::table\s*\(\s*['\"](\w+)['\"]/", $content, $alters);
            foreach ($alters[1] as $table) {
                $exists = isset($current[$table]);
                $diff[] = [
                    'type'        => $exists ? 'will_alter' : 'missing_base_table',
                    'migration'   => $migration['name'],
                    'table'       => $table,
                    'description' => $exists
                        ? 'Migration will alter this existing table'
                        : 'WARNING: Migration alters a table that does not exist',
                    'severity'    => $exists ? 'success' : 'critical',
                    'action_sql'  => null,
                ];
            }
        }

        return $diff;
    }

    public function applySchemaDiffItem(string $actionSql): array
    {
        try {
            DB::unprepared($actionSql);
            return ['status' => 'success', 'message' => 'Applied successfully'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // ── Migration Manager ──────────────────────────────────────────────────────

    public function getMigrationStatus(): array
    {
        $ran = DB::table('migrations')
            ->orderBy('batch')
            ->orderBy('migration')
            ->get(['migration', 'batch'])
            ->keyBy('migration')
            ->toArray();

        $files = glob(database_path('migrations/*.php')) ?: [];
        sort($files);

        return array_map(function (string $file) use ($ran) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            return [
                'name'   => $name,
                'file'   => $file,
                'status' => isset($ran[$name]) ? 'ran' : 'pending',
                'batch'  => isset($ran[$name]) ? $ran[$name]->batch : null,
            ];
        }, $files);
    }

    public function getPendingMigrations(): array
    {
        return array_values(array_filter(
            $this->getMigrationStatus(),
            fn($m) => $m['status'] === 'pending'
        ));
    }

    public function runPendingMigrations(): array
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $raw = Artisan::output();
        } catch (\Throwable $e) {
            return ['ERROR: ' . $e->getMessage()];
        }

        return array_values(array_filter(
            array_map('trim', explode("\n", $raw)),
            fn($l) => !empty($l)
        ));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getAvailableTables(): array
    {
        try {
            return collect(DB::select('SHOW TABLES'))
                ->map(fn($r) => array_values((array) $r)[0])
                ->sort()
                ->values()
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    public function getTableColumns(string $table): array
    {
        try {
            return Schema::getColumnListing($table);
        } catch (\Throwable) {
            return [];
        }
    }
}
