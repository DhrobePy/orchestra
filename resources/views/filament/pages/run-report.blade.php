<x-filament-panels::page>
<style>
    .rr-wrap { display:flex; flex-direction:column; gap:1.5rem; }
    .rr-filter-bar { display:flex; flex-wrap:wrap; gap:.75rem; align-items:flex-end;
        background:var(--rr-panel, #f9fafb); border:1px solid #e5e7eb;
        border-radius:10px; padding:1rem 1.25rem; }
    .dark .rr-filter-bar { background:#1f2937; border-color:#374151; }
    .rr-filter-group { display:flex; flex-direction:column; gap:.3rem; min-width:180px; }
    .rr-filter-group label { font-size:.8rem; font-weight:600; color:#374151; }
    .dark .rr-filter-group label { color:#d1d5db; }
    .rr-filter-group input, .rr-filter-group select {
        border:1px solid #d1d5db; border-radius:6px; padding:.45rem .7rem;
        font-size:.875rem; background:#fff; color:#111;
        outline:none; transition:border-color .15s; }
    .dark .rr-filter-group input, .dark .rr-filter-group select {
        background:#374151; border-color:#4b5563; color:#f3f4f6; }
    .rr-filter-group input:focus, .rr-filter-group select:focus { border-color:#6366f1; }
    .rr-daterange { display:flex; gap:.4rem; align-items:center; }
    .rr-daterange span { font-size:.8rem; color:#9ca3af; }

    .rr-actions { display:flex; gap:.6rem; }
    .rr-btn { display:inline-flex; align-items:center; gap:.4rem;
        padding:.5rem 1.1rem; border-radius:8px; font-size:.875rem;
        font-weight:600; cursor:pointer; border:none; transition:all .15s; }
    .rr-btn-run { background:#4f46e5; color:#fff; }
    .rr-btn-run:hover { background:#4338ca; }
    .rr-btn-export { background:#059669; color:#fff; }
    .rr-btn-export:hover { background:#047857; }
    .rr-btn svg { width:16px; height:16px; }

    .rr-table-wrap { overflow-x:auto; border:1px solid #e5e7eb; border-radius:10px; }
    .dark .rr-table-wrap { border-color:#374151; }
    .rr-table { width:100%; border-collapse:collapse; font-size:.875rem; }
    .rr-table th { background:#f3f4f6; color:#374151; text-align:left;
        padding:.7rem 1rem; font-weight:700; border-bottom:2px solid #e5e7eb;
        white-space:nowrap; }
    .dark .rr-table th { background:#1f2937; color:#d1d5db; border-color:#374151; }
    .rr-table td { padding:.65rem 1rem; border-bottom:1px solid #f3f4f6; color:#111827; }
    .dark .rr-table td { border-color:#374151; color:#e5e7eb; }
    .rr-table tr:hover td { background:#f9fafb; }
    .dark .rr-table tr:hover td { background:#1f2937; }
    .rr-currency { text-align:right; font-variant-numeric:tabular-nums; }
    .rr-badge-yes { background:#dcfce7; color:#166534; padding:.15rem .5rem; border-radius:4px; font-size:.8rem; }
    .rr-badge-no  { background:#fee2e2; color:#991b1b; padding:.15rem .5rem; border-radius:4px; font-size:.8rem; }

    .rr-pagination { display:flex; align-items:center; gap:.75rem; justify-content:flex-end; font-size:.875rem; color:#6b7280; }
    .rr-page-btn { padding:.35rem .8rem; border:1px solid #d1d5db; border-radius:6px;
        background:#fff; cursor:pointer; font-size:.875rem; transition:all .15s; }
    .rr-page-btn:hover:not(:disabled) { background:#f3f4f6; }
    .rr-page-btn:disabled { opacity:.4; cursor:not-allowed; }
    .dark .rr-page-btn { background:#374151; border-color:#4b5563; color:#d1d5db; }

    .rr-empty { text-align:center; padding:3rem; color:#9ca3af; font-size:.95rem; }
    .rr-summary { font-size:.8rem; color:#6b7280; }
</style>

<div class="rr-wrap">

    {{-- ── Filters ───────────────────────────────────────────────────────── --}}
    @if(!empty($this->report->filters))
    <div class="rr-filter-bar">
        @foreach($this->report->filters as $filter)
            @php $field = $filter['field']; $type = $filter['type'] ?? 'text'; @endphp

            @if($type === 'date_range')
                <div class="rr-filter-group">
                    <label>{{ $filter['label'] }}</label>
                    <div class="rr-daterange">
                        <input type="date" wire:model="filters.{{ $field }}.from" placeholder="From">
                        <span>—</span>
                        <input type="date" wire:model="filters.{{ $field }}.to" placeholder="To">
                    </div>
                </div>
            @elseif($type === 'select')
                @php
                    $src = $this->report->data_source;
                    $opts = \App\Services\ReportQueryBuilderService::sources()[$src]['filters'][$field]['options'] ?? [];
                @endphp
                <div class="rr-filter-group">
                    <label>{{ $filter['label'] }}</label>
                    <select wire:model="filters.{{ $field }}">
                        <option value="">All</option>
                        @foreach($opts as $val => $lbl)
                            <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="rr-filter-group">
                    <label>{{ $filter['label'] }}</label>
                    <input type="text" wire:model="filters.{{ $field }}" placeholder="Search…">
                </div>
            @endif
        @endforeach

        <div class="rr-actions" style="margin-top:auto;">
            <button wire:click="run" wire:loading.attr="disabled" class="rr-btn rr-btn-run">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/></svg>
                <span wire:loading.remove wire:target="run">Run</span>
                <span wire:loading wire:target="run">Running…</span>
            </button>
        </div>
    </div>
    @else
    <div class="rr-actions">
        <button wire:click="run" wire:loading.attr="disabled" class="rr-btn rr-btn-run">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/></svg>
            <span wire:loading.remove wire:target="run">Run Report</span>
            <span wire:loading wire:target="run">Running…</span>
        </button>
    </div>
    @endif

    {{-- ── Results table ─────────────────────────────────────────────────── --}}
    @if($this->hasRun)
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem;">
        <span class="rr-summary">
            Showing {{ ($this->page - 1) * $this->perPage + 1 }}–{{ min($this->page * $this->perPage, $this->totalRows) }}
            of {{ number_format($this->totalRows) }} rows
        </span>
        @if(!empty($this->rows))
        <button wire:click="exportExcel" class="rr-btn rr-btn-export">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export Excel
        </button>
        @endif
    </div>

    @if(!empty($this->rows))
    <div class="rr-table-wrap">
        <table class="rr-table">
            <thead>
                <tr>
                    @foreach($this->columns as $col)
                        <th>{{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($this->rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td @class(['rr-currency' => $cell['type'] === 'currency'])>
                            @if($cell['type'] === 'currency')
                                {{ is_numeric($cell['value']) ? number_format((float)$cell['value'], 2) : ($cell['value'] ?? '—') }}
                            @elseif($cell['type'] === 'boolean')
                                @if($cell['value'])
                                    <span class="rr-badge-yes">Yes</span>
                                @else
                                    <span class="rr-badge-no">No</span>
                                @endif
                            @elseif($cell['type'] === 'date')
                                {{ $cell['value'] ? \Carbon\Carbon::parse($cell['value'])->format('d M Y') : '—' }}
                            @else
                                {{ $cell['value'] ?? '—' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @php $maxPage = (int) ceil($this->totalRows / $this->perPage); @endphp
    @if($maxPage > 1)
    <div class="rr-pagination">
        <button wire:click="prevPage" class="rr-page-btn" @disabled($this->page <= 1)>← Prev</button>
        <span>Page {{ $this->page }} of {{ $maxPage }}</span>
        <button wire:click="nextPage" class="rr-page-btn" @disabled($this->page >= $maxPage)>Next →</button>
    </div>
    @endif

    @else
        <div class="rr-empty">No data found for the selected filters.</div>
    @endif
    @endif

</div>
</x-filament-panels::page>
