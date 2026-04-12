<?php

namespace App\Filament\Pages;

use App\Services\DatabaseMaintenanceService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

class DatabaseMaintenancePage extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon  = null;
    protected static string|UnitEnum|null    $navigationGroup = 'Settings';
    protected static ?string                 $navigationLabel = 'Database Maintenance';
    protected static ?int                    $navigationSort  = 99;

    protected string $view = 'filament.pages.database-maintenance';

    // ── SQL Import ─────────────────────────────────────────────────────────────
    public $sqlFile                = null;
    public array $parsedStatements = [];
    public array $selectedIds      = [];
    public array $sqlResults       = [];
    public bool  $sqlParsed        = false;
    public bool  $showSqlResults   = false;

    // ── CSV Import ─────────────────────────────────────────────────────────────
    public $csvFile              = null;
    public array $csvData        = [];
    public string $csvTable      = '';
    public array $csvColumnMap   = [];
    public string $csvStrategy   = 'ignore';
    public array $csvResults     = [];
    public bool  $csvParsed      = false;

    // ── Schema Comparison ──────────────────────────────────────────────────────
    public string $schemaSource       = 'migrations'; // 'migrations' | 'sql'
    public array  $schemaDiff         = [];
    public bool   $schemaDiffLoaded   = false;
    public array  $schemaApplyResults = [];

    // ── Migration Manager ──────────────────────────────────────────────────────
    public array $migrationStatus  = [];
    public array $migrationOutput  = [];

    // ── Authorization ──────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public function mount(): void
    {
        $this->loadMigrations();
    }

    // ── SQL Tab ────────────────────────────────────────────────────────────────

    public function parseSql(): void
    {
        $this->validate([
            'sqlFile' => ['required', 'file', 'max:102400', function ($attr, $value, $fail) {
                $ext = strtolower($value->getClientOriginalExtension());
                if (!in_array($ext, ['sql', 'txt'])) {
                    $fail('File must be .sql or .txt');
                }
            }],
        ]);

        $path = $this->sqlFile->getRealPath();

        try {
            $service = app(DatabaseMaintenanceService::class);
            $all = $service->parseSqlFile($path);

            // Cache full SQL server-side (keyed per user) — keeps Livewire payload small
            $cacheKey = 'dbm_sql_' . auth()->id();
            Cache::put($cacheKey, collect($all)->keyBy('id')->toArray(), now()->addHours(2));

            // Only keep display fields in Livewire property (no full SQL string)
            $this->parsedStatements = collect($all)->map(fn($s) => [
                'id'      => $s['id'],
                'type'    => $s['type'],
                'table'   => $s['table'],
                'preview' => $s['preview'],
                'safe'    => $s['safe'],
            ])->toArray();

            $this->selectedIds    = collect($this->parsedStatements)->where('safe', true)->pluck('id')->toArray();
            $this->sqlParsed      = true;
            $this->showSqlResults = false;
            $this->sqlResults     = [];

            Notification::make()->title('Parsed ' . count($all) . ' statement(s)')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Parse Error')->body($e->getMessage())->danger()->send();
        }
    }

    public function toggleStatement(string $id): void
    {
        if (in_array($id, $this->selectedIds)) {
            $this->selectedIds = array_values(array_filter($this->selectedIds, fn($i) => $i !== $id));
        } else {
            $this->selectedIds[] = $id;
        }
    }

    public function selectAllSafe(): void
    {
        $this->selectedIds = collect($this->parsedStatements)->where('safe', true)->pluck('id')->toArray();
    }

    public function selectAll(): void
    {
        $this->selectedIds = collect($this->parsedStatements)->pluck('id')->toArray();
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    public function executeSql(): void
    {
        if (empty($this->selectedIds)) {
            Notification::make()->title('No statements selected')->warning()->send();
            return;
        }

        $cached = Cache::get('dbm_sql_' . auth()->id(), []);
        $toRun  = collect($this->selectedIds)
            ->map(fn($id) => $cached[$id] ?? null)
            ->filter()
            ->values()
            ->toArray();

        if (empty($toRun)) {
            Notification::make()->title('Session expired — please re-upload the SQL file')->warning()->send();
            return;
        }

        $this->sqlResults     = app(DatabaseMaintenanceService::class)->executeStatements($toRun);
        $this->showSqlResults = true;

        $ok  = collect($this->sqlResults)->where('status', 'success')->count();
        $err = collect($this->sqlResults)->where('status', 'error')->count();

        Notification::make()
            ->title("Executed: {$ok} ok" . ($err ? ", {$err} failed" : ''))
            ->color($err > 0 ? 'warning' : 'success')
            ->send();

        $this->loadMigrations();
    }

    // ── CSV Tab ────────────────────────────────────────────────────────────────

    public function parseCsv(): void
    {
        $this->validate([
            'csvFile' => ['required', 'file', 'max:102400', function ($attr, $value, $fail) {
                $ext = strtolower($value->getClientOriginalExtension());
                if (!in_array($ext, ['csv', 'txt', 'xlsx', 'xls'])) {
                    $fail('File must be .csv, .txt, .xlsx, or .xls');
                }
            }],
        ]);

        $path = $this->csvFile->getRealPath();
        $ext  = strtolower($this->csvFile->getClientOriginalExtension());

        try {
            $service = app(DatabaseMaintenanceService::class);

            $this->csvData   = in_array($ext, ['xlsx', 'xls'])
                ? $service->parseExcelFile($path)
                : $service->parseCsvFile($path);

            $this->csvParsed = true;
            $this->csvResults = [];

            if ($this->csvTable) {
                $this->autoMapColumns();
            }

            Notification::make()
                ->title("Found {$this->csvData['total_rows']} row(s), " . count($this->csvData['headers']) . ' column(s)')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Parse Error')->body($e->getMessage())->danger()->send();
        }
    }

    public function updatedCsvTable(): void
    {
        if ($this->csvParsed && $this->csvTable) {
            $this->autoMapColumns();
        }
    }

    private function autoMapColumns(): void
    {
        $tableColumns = app(DatabaseMaintenanceService::class)->getTableColumns($this->csvTable);
        $map = [];
        foreach ($this->csvData['headers'] ?? [] as $header) {
            $map[$header] = in_array($header, $tableColumns) ? $header : '';
        }
        $this->csvColumnMap = $map;
    }

    public function importCsv(): void
    {
        if (!$this->csvFile || !$this->csvTable) {
            Notification::make()->title('Select a target table first')->warning()->send();
            return;
        }

        $path = $this->csvFile->getRealPath();
        $ext  = strtolower($this->csvFile->getClientOriginalExtension());

        try {
            // For Excel files, convert to temp CSV first
            if (in_array($ext, ['xlsx', 'xls'])) {
                Notification::make()->title('Excel import: use CSV export for bulk import')->warning()->send();
                return;
            }

            $this->csvResults = app(DatabaseMaintenanceService::class)->importCsvFile(
                $path,
                $this->csvTable,
                $this->csvStrategy,
                $this->csvColumnMap,
                $this->csvData['delimiter'] ?? ','
            );

            $r = $this->csvResults;
            Notification::make()
                ->title('Import complete')
                ->body("Inserted: {$r['inserted']} · Updated: {$r['updated']} · Skipped: {$r['skipped']} · Errors: {$r['errors']}")
                ->color($r['errors'] > 0 ? 'warning' : 'success')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Import Error')->body($e->getMessage())->danger()->send();
        }
    }

    // ── Schema Tab ─────────────────────────────────────────────────────────────

    public function loadSchemaDiff(): void
    {
        $service = app(DatabaseMaintenanceService::class);

        try {
            if ($this->schemaSource === 'sql') {
                $cached  = Cache::get('dbm_sql_' . auth()->id(), []);
                $creates = collect($cached)
                    ->where('type', 'CREATE_TABLE')
                    ->values()
                    ->toArray();

                if (empty($creates)) {
                    Notification::make()
                        ->title('No CREATE TABLE statements found in parsed SQL')
                        ->warning()
                        ->send();
                    return;
                }

                $this->schemaDiff = $service->compareSchemaFromSql($creates);
            } else {
                $this->schemaDiff = $service->compareSchemaFromMigrations();
            }

            $this->schemaDiffLoaded   = true;
            $this->schemaApplyResults = [];
        } catch (\Throwable $e) {
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
        }
    }

    public function applySchemaFix(int $index): void
    {
        $item = $this->schemaDiff[$index] ?? null;
        if (!$item || empty($item['action_sql'])) return;

        $result = app(DatabaseMaintenanceService::class)->applySchemaDiffItem($item['action_sql']);
        $this->schemaApplyResults[$index] = $result;

        if ($result['status'] === 'success') {
            Notification::make()->title("Table `{$item['table']}` updated")->success()->send();
            $this->loadSchemaDiff();
        } else {
            Notification::make()->title('Error')->body($result['message'])->danger()->send();
        }
    }

    // ── Migrations Tab ─────────────────────────────────────────────────────────

    public function loadMigrations(): void
    {
        try {
            $this->migrationStatus = app(DatabaseMaintenanceService::class)->getMigrationStatus();
        } catch (\Throwable) {
            $this->migrationStatus = [];
        }
    }

    public function runPendingMigrations(): void
    {
        $pending = collect($this->migrationStatus)->where('status', 'pending')->count();

        if ($pending === 0) {
            Notification::make()->title('No pending migrations')->info()->send();
            return;
        }

        $this->migrationOutput = app(DatabaseMaintenanceService::class)->runPendingMigrations();
        $this->loadMigrations();

        $hasError = collect($this->migrationOutput)
            ->contains(fn($l) => str_contains(strtolower($l), 'error'));

        Notification::make()
            ->title($hasError ? 'Completed with errors' : 'Migrations ran successfully')
            ->color($hasError ? 'warning' : 'success')
            ->send();
    }

    // ── Computed ───────────────────────────────────────────────────────────────

    #[Computed]
    public function availableTables(): array
    {
        return app(DatabaseMaintenanceService::class)->getAvailableTables();
    }

    #[Computed]
    public function tableColumns(): array
    {
        if (!$this->csvTable) return [];
        return app(DatabaseMaintenanceService::class)->getTableColumns($this->csvTable);
    }

    #[Computed]
    public function pendingCount(): int
    {
        return collect($this->migrationStatus)->where('status', 'pending')->count();
    }

    #[Computed]
    public function sqlSelectedCount(): int
    {
        return count($this->selectedIds);
    }

    // ── Type badge helpers (used in blade) ─────────────────────────────────────

    public function typeColor(string $type): string
    {
        return match ($type) {
            'CREATE_TABLE'   => 'text-green-700 bg-green-100 dark:bg-green-900/40 dark:text-green-300',
            'ALTER_TABLE'    => 'text-blue-700 bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300',
            'INSERT'         => 'text-purple-700 bg-purple-100 dark:bg-purple-900/40 dark:text-purple-300',
            'UPDATE'         => 'text-yellow-700 bg-yellow-100 dark:bg-yellow-900/40 dark:text-yellow-300',
            'DROP_TABLE',
            'DROP_DATABASE'  => 'text-red-700 bg-red-100 dark:bg-red-900/40 dark:text-red-300',
            'TRUNCATE',
            'DELETE'         => 'text-red-700 bg-red-100 dark:bg-red-900/40 dark:text-red-300',
            'CREATE_INDEX'   => 'text-cyan-700 bg-cyan-100 dark:bg-cyan-900/40 dark:text-cyan-300',
            default          => 'text-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    public function severityColor(string $severity): string
    {
        return match ($severity) {
            'critical' => 'text-red-700 bg-red-50 dark:bg-red-900/30 dark:text-red-300',
            'warning'  => 'text-yellow-700 bg-yellow-50 dark:bg-yellow-900/30 dark:text-yellow-300',
            'success'  => 'text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-300',
            default    => 'text-blue-700 bg-blue-50 dark:bg-blue-900/30 dark:text-blue-300',
        };
    }
}
