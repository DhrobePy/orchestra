<?php

namespace App\Filament\Pages;

use App\Models\CustomReport;
use App\Services\ReportQueryBuilderService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

class RunReportPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = null;
    protected static bool                    $shouldRegisterNavigation = false;
    protected string                         $view = 'filament.pages.run-report';

    public int     $reportId   = 0;
    public array   $filters    = [];
    public array   $rows       = [];
    public array   $columns    = [];
    public bool    $hasRun     = false;
    public int     $page       = 1;
    public int     $perPage    = 50;
    public int     $totalRows  = 0;

    public ?CustomReport $report = null;

    public function mount(int $report): void
    {
        $this->report   = CustomReport::findOrFail($report);
        $this->reportId = $report;
        $this->columns  = $this->report->columns ?? [];

        // Initialise filter values as empty
        foreach ($this->report->filters ?? [] as $f) {
            $field = $f['field'];
            $this->filters[$field] = ($f['type'] === 'date_range') ? ['from' => '', 'to' => ''] : '';
        }
    }

    public function run(): void
    {
        $this->page = 1;
        $this->fetch();
    }

    public function fetch(): void
    {
        $service = app(ReportQueryBuilderService::class);

        try {
            $query          = $service->buildQuery($this->report, $this->filters);
            $this->totalRows = (clone $query)->count();

            $rawRows    = (clone $query)->forPage($this->page, $this->perPage)->get();
            $this->rows = $rawRows->map(fn ($row) => $service->formatRow($row, $this->columns))->toArray();
            $this->hasRun = true;
        } catch (\Throwable $e) {
            Notification::make()->title('Error running report')->body($e->getMessage())->danger()->send();
        }
    }

    public function nextPage(): void
    {
        $maxPage = (int) ceil($this->totalRows / $this->perPage);
        if ($this->page < $maxPage) {
            $this->page++;
            $this->fetch();
        }
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
            $this->fetch();
        }
    }

    public function exportExcel(): BinaryFileResponse
    {
        $service = app(ReportQueryBuilderService::class);
        $query   = $service->buildQuery($this->report, $this->filters);
        $raw     = $query->get();

        $cols    = $this->columns;
        $headers = array_column($cols, 'label');

        $data = $raw->map(function ($row) use ($service, $cols) {
            return collect($service->formatRow($row, $cols))
                ->pluck('value')
                ->toArray();
        });

        $export = new class($headers, $data) implements FromCollection, WithHeadings {
            public function __construct(
                private array $headers,
                private \Illuminate\Support\Collection $data
            ) {}
            public function collection() { return $this->data; }
            public function headings(): array { return $this->headers; }
        };

        $filename = str($this->report->name)->slug() . '_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download($export, $filename);
    }

    public function getTitle(): string
    {
        return $this->report?->name ?? 'Run Report';
    }
}
