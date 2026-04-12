<x-filament-panels::page>
<style>
/* ── Tabs ─────────────────────────────────────────────────────────────────── */
.dbm-tabs { display:flex; flex-wrap:wrap; gap:4px; padding:4px; border-radius:12px; border:1px solid #e2e8f0; background:#f8fafc; }
.dark .dbm-tabs { border-color:rgba(255,255,255,.08); background:rgba(255,255,255,.03); }
.dbm-tab { display:flex; align-items:center; gap:8px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:500; cursor:pointer; border:none; background:transparent; color:#64748b; transition:all .15s; }
.dark .dbm-tab { color:#94a3b8; }
.dbm-tab:hover { color:#334155; background:rgba(0,0,0,.04); }
.dark .dbm-tab:hover { color:#e2e8f0; background:rgba(255,255,255,.06); }
.dbm-tab.active { background:#fff; color:#6366f1; box-shadow:0 1px 3px rgba(0,0,0,.1); }
.dark .dbm-tab.active { background:#1e293b; color:#818cf8; }
.dbm-tab svg { width:16px; height:16px; flex-shrink:0; }
.dbm-badge-pill { display:inline-flex; align-items:center; justify-content:center; padding:1px 6px; border-radius:999px; font-size:10px; font-weight:700; background:#f59e0b; color:#fff; min-width:18px; }

/* ── Cards ────────────────────────────────────────────────────────────────── */
.dbm-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
.dark .dbm-card { background:#0f172a; border-color:rgba(255,255,255,.08); }
.dbm-card-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding:14px 20px; border-bottom:1px solid #f1f5f9; }
.dark .dbm-card-header { border-bottom-color:rgba(255,255,255,.06); }
.dbm-card-title { font-size:13px; font-weight:600; color:#1e293b; display:flex; align-items:center; gap:6px; }
.dark .dbm-card-title { color:#e2e8f0; }
.dbm-card-title svg { width:16px; height:16px; }
.dbm-card-body { padding:20px; }
.dbm-card-footer { padding:12px 20px; border-top:1px solid #f1f5f9; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.dark .dbm-card-footer { border-top-color:rgba(255,255,255,.06); }

/* ── Warning Banner ───────────────────────────────────────────────────────── */
.dbm-warn { display:flex; gap:12px; padding:14px 18px; background:#fef2f2; border:1px solid #fecaca; border-radius:10px; }
.dark .dbm-warn { background:rgba(239,68,68,.07); border-color:rgba(239,68,68,.25); }
.dbm-warn svg { width:18px; height:18px; flex-shrink:0; margin-top:2px; color:#dc2626; }
.dark .dbm-warn svg { color:#f87171; }
.dbm-warn-title { font-size:13px; font-weight:600; color:#991b1b; }
.dark .dbm-warn-title { color:#fca5a5; }
.dbm-warn-body { font-size:12px; color:#b91c1c; margin-top:2px; }
.dark .dbm-warn-body { color:#f87171; }

/* ── Form Controls ────────────────────────────────────────────────────────── */
.dbm-label { display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:4px; }
.dark .dbm-label { color:#94a3b8; }
.dbm-input { width:100%; padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; background:#fff; color:#0f172a; transition:border-color .15s; }
.dark .dbm-input { background:#1e293b; border-color:rgba(255,255,255,.1); color:#f1f5f9; color-scheme:dark; }
.dbm-input:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.12); }
.dbm-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; background-size:16px; padding-right:36px; cursor:pointer; }
.dbm-file { width:100%; font-size:12px; padding:6px 10px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; color:#475569; cursor:pointer; }
.dark .dbm-file { background:#1e293b; border-color:rgba(255,255,255,.1); color:#94a3b8; }

/* ── Table ────────────────────────────────────────────────────────────────── */
.dbm-table { width:100%; font-size:12px; border-collapse:collapse; }
.dbm-table thead tr { background:#f8fafc; }
.dark .dbm-table thead tr { background:#0f172a; }
.dbm-table thead th { padding:8px 14px; text-align:left; font-size:11px; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.dbm-table tbody tr { border-top:1px solid #f1f5f9; transition:background .1s; }
.dark .dbm-table tbody tr { border-top-color:rgba(255,255,255,.05); }
.dbm-table tbody tr:hover { background:#f8fafc; }
.dark .dbm-table tbody tr:hover { background:rgba(255,255,255,.03); }
.dbm-table tbody td { padding:9px 14px; vertical-align:middle; color:#334155; }
.dark .dbm-table tbody td { color:#cbd5e1; }
.dbm-table .mono { font-family:ui-monospace,monospace; font-size:11px; }
.dbm-table .truncate-cell { max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dbm-row-danger { background:rgba(239,68,68,.04) !important; }
.dark .dbm-row-danger { background:rgba(239,68,68,.07) !important; }

/* ── Type Badges ──────────────────────────────────────────────────────────── */
.dbm-badge { display:inline-flex; align-items:center; padding:2px 7px; border-radius:5px; font-size:10px; font-weight:700; white-space:nowrap; text-transform:uppercase; letter-spacing:.03em; }
.dbm-badge-create  { background:rgba(16,185,129,.1);  color:#065f46; }
.dark .dbm-badge-create  { background:rgba(16,185,129,.15); color:#6ee7b7; }
.dbm-badge-alter   { background:rgba(59,130,246,.1);  color:#1e40af; }
.dark .dbm-badge-alter   { background:rgba(59,130,246,.15); color:#93c5fd; }
.dbm-badge-insert  { background:rgba(139,92,246,.1);  color:#5b21b6; }
.dark .dbm-badge-insert  { background:rgba(139,92,246,.15); color:#c4b5fd; }
.dbm-badge-update  { background:rgba(245,158,11,.1);  color:#92400e; }
.dark .dbm-badge-update  { background:rgba(245,158,11,.15); color:#fcd34d; }
.dbm-badge-danger  { background:rgba(239,68,68,.1);   color:#991b1b; }
.dark .dbm-badge-danger  { background:rgba(239,68,68,.15);  color:#fca5a5; }
.dbm-badge-index   { background:rgba(6,182,212,.1);   color:#155e75; }
.dark .dbm-badge-index   { background:rgba(6,182,212,.15);  color:#67e8f9; }
.dbm-badge-neutral { background:rgba(100,116,139,.1); color:#475569; }
.dark .dbm-badge-neutral { background:rgba(100,116,139,.15); color:#94a3b8; }

/* ── Severity Badges ──────────────────────────────────────────────────────── */
.dbm-sev-critical { background:rgba(239,68,68,.08);  color:#991b1b;  padding:3px 8px; border-radius:5px; font-size:10px; font-weight:700; text-transform:uppercase; }
.dark .dbm-sev-critical { background:rgba(239,68,68,.15); color:#fca5a5; }
.dbm-sev-warning  { background:rgba(245,158,11,.08); color:#92400e; padding:3px 8px; border-radius:5px; font-size:10px; font-weight:700; text-transform:uppercase; }
.dark .dbm-sev-warning  { background:rgba(245,158,11,.15); color:#fcd34d; }
.dbm-sev-success  { background:rgba(16,185,129,.08); color:#065f46;  padding:3px 8px; border-radius:5px; font-size:10px; font-weight:700; text-transform:uppercase; }
.dark .dbm-sev-success  { background:rgba(16,185,129,.15); color:#6ee7b7; }
.dbm-sev-info     { background:rgba(59,130,246,.08); color:#1e40af;  padding:3px 8px; border-radius:5px; font-size:10px; font-weight:700; text-transform:uppercase; }
.dark .dbm-sev-info     { background:rgba(59,130,246,.15); color:#93c5fd; }

/* ── Status Badges ────────────────────────────────────────────────────────── */
.dbm-status-ran     { background:rgba(16,185,129,.1);  color:#065f46;  padding:2px 9px; border-radius:999px; font-size:10px; font-weight:700; }
.dark .dbm-status-ran     { background:rgba(16,185,129,.15); color:#6ee7b7; }
.dbm-status-pending { background:rgba(245,158,11,.1);  color:#92400e; padding:2px 9px; border-radius:999px; font-size:10px; font-weight:700; }
.dark .dbm-status-pending { background:rgba(245,158,11,.15); color:#fcd34d; }

/* ── Result rows ──────────────────────────────────────────────────────────── */
.dbm-row-ok    { background:rgba(16,185,129,.04) !important; }
.dbm-row-error { background:rgba(239,68,68,.04) !important; }
.dark .dbm-row-ok    { background:rgba(16,185,129,.07) !important; }
.dark .dbm-row-error { background:rgba(239,68,68,.07) !important; }
.dbm-text-ok    { color:#059669; }
.dbm-text-error { color:#dc2626; }
.dark .dbm-text-ok    { color:#34d399; }
.dark .dbm-text-error { color:#f87171; }

/* ── Terminal ─────────────────────────────────────────────────────────────── */
.dbm-terminal { background:#0f172a; border-radius:10px; padding:16px; font-family:ui-monospace,monospace; font-size:12px; line-height:1.6; max-height:220px; overflow-y:auto; }
.dbm-terminal p { margin:0; }
.dbm-terminal .t-ok      { color:#4ade80; }
.dbm-terminal .t-running { color:#fbbf24; }
.dbm-terminal .t-error   { color:#f87171; }
.dbm-terminal .t-label   { color:#94a3b8; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px; }

/* ── Misc ─────────────────────────────────────────────────────────────────── */
.dbm-sel-group { display:flex; gap:4px; }
.dbm-sel-btn { padding:4px 10px; border-radius:5px; font-size:11px; font-weight:600; cursor:pointer; border:none; background:transparent; transition:background .12s; }
.dbm-sel-btn-safe   { color:#059669; }
.dbm-sel-btn-safe:hover   { background:rgba(16,185,129,.1); }
.dbm-sel-btn-all    { color:#3b82f6; }
.dbm-sel-btn-all:hover    { background:rgba(59,130,246,.1); }
.dbm-sel-btn-none   { color:#64748b; }
.dbm-sel-btn-none:hover   { background:rgba(100,116,139,.1); }
.dbm-scroll { max-height:300px; overflow-y:auto; }
.dbm-col-map-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:8px; }
.dbm-col-map-item { border:1px solid #e2e8f0; border-radius:8px; padding:8px 10px; background:#f8fafc; }
.dark .dbm-col-map-item { border-color:rgba(255,255,255,.08); background:rgba(255,255,255,.02); }
.dbm-col-map-item p { font-size:10px; font-weight:700; color:#475569; margin:0 0 5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dark .dbm-col-map-item p { color:#94a3b8; }
.dbm-results-bar { display:flex; flex-wrap:wrap; gap:20px; font-size:13px; }
.dbm-info-note { font-size:12px; color:#64748b; margin-top:10px; padding:10px 14px; background:#f8fafc; border-radius:8px; border-left:3px solid #e2e8f0; }
.dark .dbm-info-note { color:#94a3b8; background:rgba(255,255,255,.03); border-left-color:rgba(255,255,255,.1); }
.dbm-checkbox { width:14px; height:14px; cursor:pointer; accent-color:#6366f1; }
</style>

{{-- Warning Banner --}}
<div class="dbm-warn">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
    <div>
        <p class="dbm-warn-title">Super Admin Only — Use with caution</p>
        <p class="dbm-warn-body">SQL execution runs directly on the database. Always backup before importing. DROP / TRUNCATE / DELETE are excluded from default selection.</p>
    </div>
</div>

{{-- Tab Container --}}
<div x-data="{ tab: 'sql' }" class="space-y-4" style="margin-top:16px;">

    {{-- Tab Bar --}}
    <div class="dbm-tabs">
        @foreach ([
            ['id' => 'sql',        'label' => 'SQL Import',        'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
            ['id' => 'csv',        'label' => 'CSV / Excel Import', 'icon' => 'M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75.125v-5.625c0-.621.504-1.125 1.125-1.125h1.5m0 0V9.75m0 2.625h9m-9 0v3.75m9-3.75v3.75m0 0h1.5c.621 0 1.125-.504 1.125-1.125v-5.625c0-.621-.504-1.125-1.125-1.125H6.375m9 0V9.75m0 0H6.375m3.75-4.5V3.375C10.125 2.754 9.621 2.25 9 2.25H7.5c-.621 0-1.125.504-1.125 1.125v1.875'],
            ['id' => 'schema',     'label' => 'Schema Compare',     'icon' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z'],
            ['id' => 'migrations', 'label' => 'Migrations',         'icon' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99'],
        ] as $t)
        <button
            type="button"
            class="dbm-tab"
            :class="tab === '{{ $t['id'] }}' ? 'active' : ''"
            @click="tab = '{{ $t['id'] }}'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $t['icon'] }}"/></svg>
            {{ $t['label'] }}
            @if ($t['id'] === 'migrations' && $this->pendingCount > 0)
                <span class="dbm-badge-pill">{{ $this->pendingCount }}</span>
            @endif
        </button>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════ TAB: SQL IMPORT ══════════════════════════════════════ --}}
    <div x-show="tab === 'sql'" x-cloak class="space-y-4">

        <div class="dbm-card">
            <div class="dbm-card-header">
                <span class="dbm-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    Upload SQL File
                </span>
            </div>
            <div class="dbm-card-body">
                <div style="display:grid; grid-template-columns:1fr auto; gap:12px; align-items:end;">
                    <div>
                        <label class="dbm-label">SQL file (.sql or .txt) — max 100 MB</label>
                        <input type="file" wire:model="sqlFile" accept=".sql,.txt" class="dbm-file" />
                        @error('sqlFile') <p style="color:#dc2626; font-size:11px; margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                    <button
                        type="button"
                        wire:click="parseSql"
                        wire:loading.attr="disabled"
                        wire:target="parseSql"
                        style="padding:9px 18px; border-radius:8px; background:#6366f1; color:#fff; font-size:13px; font-weight:600; border:none; cursor:pointer; white-space:nowrap; display:flex; align-items:center; gap:6px;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        <span wire:loading.remove wire:target="parseSql">Parse File</span>
                        <span wire:loading wire:target="parseSql">Parsing…</span>
                    </button>
                </div>
            </div>
        </div>

        @if ($sqlParsed && !empty($parsedStatements))
        <div class="dbm-card">
            <div class="dbm-card-header">
                <span class="dbm-card-title">
                    {{ count($parsedStatements) }} Statement(s) Found
                    <span style="font-size:11px; font-weight:400; color:#94a3b8;">{{ $this->sqlSelectedCount }} selected</span>
                </span>
                <div class="dbm-sel-group">
                    <button type="button" wire:click="selectAllSafe"  class="dbm-sel-btn dbm-sel-btn-safe">Safe only</button>
                    <button type="button" wire:click="selectAll"      class="dbm-sel-btn dbm-sel-btn-all">All</button>
                    <button type="button" wire:click="clearSelection" class="dbm-sel-btn dbm-sel-btn-none">None</button>
                </div>
            </div>
            <div class="dbm-scroll">
                <table class="dbm-table">
                    <thead><tr>
                        <th style="width:36px;"></th>
                        <th>Type</th>
                        <th>Table</th>
                        <th>Preview</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($parsedStatements as $stmt)
                        <tr class="{{ !$stmt['safe'] ? 'dbm-row-danger' : '' }}">
                            <td style="text-align:center;">
                                <input type="checkbox" class="dbm-checkbox"
                                    wire:click="toggleStatement('{{ $stmt['id'] }}')"
                                    {{ in_array($stmt['id'], $selectedIds) ? 'checked' : '' }} />
                            </td>
                            <td>
                                @php
                                    $tc = match($stmt['type']) {
                                        'CREATE_TABLE'  => 'dbm-badge-create',
                                        'ALTER_TABLE'   => 'dbm-badge-alter',
                                        'INSERT'        => 'dbm-badge-insert',
                                        'UPDATE'        => 'dbm-badge-update',
                                        'DROP_TABLE', 'DROP_DATABASE', 'TRUNCATE', 'DELETE' => 'dbm-badge-danger',
                                        'CREATE_INDEX'  => 'dbm-badge-index',
                                        default         => 'dbm-badge-neutral',
                                    };
                                @endphp
                                <span class="dbm-badge {{ $tc }}">{{ str_replace('_', ' ', $stmt['type']) }}</span>
                            </td>
                            <td class="mono">{{ $stmt['table'] }}</td>
                            <td class="mono truncate-cell">{{ $stmt['preview'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="dbm-card-footer">
                <button
                    type="button"
                    wire:click="executeSql"
                    wire:confirm="Execute {{ $this->sqlSelectedCount }} SQL statement(s) directly on the database? This cannot be undone."
                    wire:loading.attr="disabled"
                    wire:target="executeSql"
                    style="padding:9px 18px; border-radius:8px; background:#dc2626; color:#fff; font-size:13px; font-weight:600; border:none; cursor:pointer; display:flex; align-items:center; gap:6px;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                    <span wire:loading.remove wire:target="executeSql">Execute {{ $this->sqlSelectedCount }} Statement(s)</span>
                    <span wire:loading wire:target="executeSql">Executing…</span>
                </button>
            </div>
        </div>
        @endif

        @if ($showSqlResults && !empty($sqlResults))
        <div class="dbm-card">
            <div class="dbm-card-header">
                <span class="dbm-card-title">Execution Results</span>
                <span style="font-size:12px;">
                    <span class="dbm-text-ok">{{ collect($sqlResults)->where('status','success')->count() }} ok</span>
                    &nbsp;·&nbsp;
                    <span class="dbm-text-error">{{ collect($sqlResults)->where('status','error')->count() }} failed</span>
                </span>
            </div>
            <div class="dbm-scroll">
                <table class="dbm-table">
                    <tbody>
                        @foreach ($sqlResults as $result)
                        <tr class="{{ $result['status'] === 'success' ? 'dbm-row-ok' : 'dbm-row-error' }}">
                            <td style="width:28px; text-align:center;">
                                @if ($result['status'] === 'success')
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:15px;height:15px;" class="dbm-text-ok"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:15px;height:15px;" class="dbm-text-error"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </td>
                            <td class="mono" style="width:80px;">{{ $result['table'] }}</td>
                            <td class="mono truncate-cell">{{ $result['preview'] }}</td>
                            <td class="{{ $result['status'] === 'success' ? 'dbm-text-ok' : 'dbm-text-error' }}" style="font-size:11px;">{{ $result['message'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════ TAB: CSV IMPORT ══════════════════════════════════════ --}}
    <div x-show="tab === 'csv'" x-cloak class="space-y-4">

        <div class="dbm-card">
            <div class="dbm-card-header">
                <span class="dbm-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    Upload CSV / Excel File
                </span>
            </div>
            <div class="dbm-card-body" style="display:grid; gap:16px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label class="dbm-label">File (.csv / .txt — Excel .xlsx requires maatwebsite/excel)</label>
                        <input type="file" wire:model="csvFile" accept=".csv,.txt,.xlsx,.xls" class="dbm-file" />
                        @error('csvFile') <p style="color:#dc2626; font-size:11px; margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="dbm-label">Target Table</label>
                        <select wire:model.live="csvTable" class="dbm-input dbm-select">
                            <option value="">— select table —</option>
                            @foreach ($this->availableTables as $tbl)
                                <option value="{{ $tbl }}">{{ $tbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="display:flex; align-items:flex-end; gap:16px; flex-wrap:wrap;">
                    <div>
                        <label class="dbm-label">Conflict Strategy</label>
                        <select wire:model="csvStrategy" class="dbm-input dbm-select" style="width:auto;">
                            <option value="ignore">Skip duplicates (INSERT IGNORE)</option>
                            <option value="insert">Force insert (may fail on duplicates)</option>
                            <option value="upsert">Upsert by ID (insert or update)</option>
                        </select>
                    </div>
                    <button
                        type="button"
                        wire:click="parseCsv"
                        wire:loading.attr="disabled"
                        wire:target="parseCsv"
                        style="padding:9px 18px; border-radius:8px; background:#6366f1; color:#fff; font-size:13px; font-weight:600; border:none; cursor:pointer; display:flex; align-items:center; gap:6px;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        <span wire:loading.remove wire:target="parseCsv">Parse File</span>
                        <span wire:loading wire:target="parseCsv">Parsing…</span>
                    </button>
                </div>
            </div>
        </div>

        @if ($csvParsed && !empty($csvData))
        <div class="dbm-card">
            <div class="dbm-card-header">
                <span class="dbm-card-title">Preview — {{ $csvData['total_rows'] }} row(s) · {{ count($csvData['headers']) }} column(s)</span>
            </div>

            {{-- Preview rows --}}
            <div style="overflow-x:auto; border-bottom:1px solid #f1f5f9;">
                <table class="dbm-table" style="font-size:11px;">
                    <thead><tr>
                        @foreach ($csvData['headers'] as $h)
                            <th>{{ $h }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @foreach (($csvData['preview'] ?? []) as $row)
                        <tr>
                            @foreach ($csvData['headers'] as $h)
                                <td class="truncate-cell">{{ $row[$h] ?? '' }}</td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Column mapping --}}
            @if ($csvTable && !empty($csvColumnMap))
            <div style="padding:16px 20px; border-bottom:1px solid #f1f5f9;">
                <p class="dbm-label" style="margin-bottom:10px;">
                    Map CSV columns → <span class="mono" style="color:#6366f1;">{{ $csvTable }}</span>
                </p>
                <div class="dbm-col-map-grid">
                    @foreach ($csvData['headers'] as $header)
                    <div class="dbm-col-map-item">
                        <p>{{ $header }}</p>
                        <select wire:model="csvColumnMap.{{ $header }}" class="dbm-input dbm-select" style="padding:4px 28px 4px 8px; font-size:11px;">
                            <option value="">— skip —</option>
                            @foreach ($this->tableColumns as $col)
                                <option value="{{ $col }}">{{ $col }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>
            </div>
            @elseif (!$csvTable)
            <div class="dbm-info-note">Select a target table above to configure column mapping.</div>
            @endif

            <div class="dbm-card-footer">
                <button
                    type="button"
                    wire:click="importCsv"
                    wire:confirm="Import {{ $csvData['total_rows'] }} rows into table [{{ $csvTable }}]?"
                    wire:loading.attr="disabled"
                    wire:target="importCsv"
                    @if(!$csvTable) disabled @endif
                    style="padding:9px 18px; border-radius:8px; background:{{ $csvTable ? '#10b981' : '#94a3b8' }}; color:#fff; font-size:13px; font-weight:600; border:none; cursor:{{ $csvTable ? 'pointer' : 'not-allowed' }}; display:flex; align-items:center; gap:6px;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M12 3v13.5m0 0l-4.5-4.5M12 16.5l4.5-4.5"/></svg>
                    <span wire:loading.remove wire:target="importCsv">Import into {{ $csvTable ?: '…' }}</span>
                    <span wire:loading wire:target="importCsv">Importing…</span>
                </button>
            </div>
        </div>
        @endif

        @if (!empty($csvResults))
        <div class="dbm-card">
            <div class="dbm-card-header"><span class="dbm-card-title">Import Results</span></div>
            <div style="padding:16px 20px;">
                <div class="dbm-results-bar">
                    <span class="dbm-text-ok">✓ Inserted: {{ $csvResults['inserted'] }}</span>
                    <span style="color:#3b82f6;">↻ Updated: {{ $csvResults['updated'] }}</span>
                    <span style="color:#64748b;">— Skipped: {{ $csvResults['skipped'] }}</span>
                    <span class="dbm-text-error">✗ Errors: {{ $csvResults['errors'] }}</span>
                </div>
                @if (!empty($csvResults['errorMessages']))
                <div style="margin-top:10px; display:flex; flex-direction:column; gap:4px;">
                    @foreach ($csvResults['errorMessages'] as $msg)
                        <p style="background:rgba(239,68,68,.07); padding:6px 12px; border-radius:6px; font-size:11px; color:#dc2626;">{{ $msg }}</p>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════ TAB: SCHEMA COMPARE ════════════════════════════════════ --}}
    <div x-show="tab === 'schema'" x-cloak class="space-y-4">

        <div class="dbm-card">
            <div class="dbm-card-header"><span class="dbm-card-title">Compare Database Schema</span></div>
            <div class="dbm-card-body">
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:20px;">
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; color:#475569;">
                            <input type="radio" wire:model="schemaSource" value="migrations" style="accent-color:#6366f1;" />
                            <span>Compare against pending migrations</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; color:#475569;">
                            <input type="radio" wire:model="schemaSource" value="sql" style="accent-color:#6366f1;" />
                            <span>Compare against uploaded SQL (parse SQL first)</span>
                        </label>
                    </div>
                    <button
                        type="button"
                        wire:click="loadSchemaDiff"
                        wire:loading.attr="disabled"
                        wire:target="loadSchemaDiff"
                        style="padding:9px 18px; border-radius:8px; background:#6366f1; color:#fff; font-size:13px; font-weight:600; border:none; cursor:pointer; display:flex; align-items:center; gap:6px;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        <span wire:loading.remove wire:target="loadSchemaDiff">Run Comparison</span>
                        <span wire:loading wire:target="loadSchemaDiff">Comparing…</span>
                    </button>
                </div>
                @if ($schemaSource === 'sql' && empty($parsedStatements))
                    <p class="dbm-info-note" style="border-left-color:#f59e0b;">⚠ Parse a SQL file in the SQL Import tab first.</p>
                @endif
            </div>
        </div>

        @if ($schemaDiffLoaded && !empty($schemaDiff))
        <div class="dbm-card">
            <div class="dbm-card-header">
                <span class="dbm-card-title">{{ count($schemaDiff) }} item(s)</span>
            </div>
            <div>
                @foreach ($schemaDiff as $i => $item)
                @php
                    $sevClass = match($item['severity']) {
                        'critical' => 'dbm-sev-critical',
                        'warning'  => 'dbm-sev-warning',
                        'success'  => 'dbm-sev-success',
                        default    => 'dbm-sev-info',
                    };
                @endphp
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:12px 20px; border-top:1px solid #f1f5f9;">
                    <div style="display:flex; align-items:flex-start; gap:10px; flex:1; min-width:0;">
                        <span class="{{ $sevClass }}" style="flex-shrink:0; margin-top:1px;">{{ $item['severity'] }}</span>
                        <div style="min-width:0;">
                            <p class="mono" style="font-size:13px; font-weight:600; color:#1e293b; margin:0;">{{ $item['table'] }}</p>
                            <p style="font-size:12px; color:#64748b; margin:2px 0 0;">{{ $item['description'] }}</p>
                            @if (!empty($item['migration']))
                                <p class="mono" style="font-size:10px; color:#94a3b8; margin-top:2px;">{{ $item['migration'] }}</p>
                            @endif
                        </div>
                    </div>
                    @if (!empty($item['action_sql']))
                    <button
                        type="button"
                        wire:click="applySchemaFix({{ $i }})"
                        wire:confirm="Apply this schema change to the database?"
                        style="flex-shrink:0; padding:6px 12px; border-radius:7px; background:rgba(245,158,11,.1); color:#92400e; font-size:12px; font-weight:600; border:1px solid rgba(245,158,11,.3); cursor:pointer;"
                    >Apply Fix</button>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════ TAB: MIGRATIONS ════════════════════════════════════════ --}}
    <div x-show="tab === 'migrations'" x-cloak class="space-y-4">

        <div class="dbm-card">
            <div class="dbm-card-header">
                <div>
                    <p class="dbm-card-title">Migration Status</p>
                    <p style="font-size:12px; color:#94a3b8; margin-top:2px;">
                        {{ collect($migrationStatus)->where('status','ran')->count() }} ran &nbsp;·&nbsp;
                        <span style="{{ $this->pendingCount > 0 ? 'color:#f59e0b; font-weight:600;' : '' }}">{{ $this->pendingCount }} pending</span>
                    </p>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <button
                        type="button"
                        wire:click="loadMigrations"
                        style="padding:7px 14px; border-radius:7px; background:transparent; border:1px solid #e2e8f0; color:#64748b; font-size:12px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:5px;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px;"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        Refresh
                    </button>
                    @if ($this->pendingCount > 0)
                    <button
                        type="button"
                        wire:click="runPendingMigrations"
                        wire:confirm="Run all {{ $this->pendingCount }} pending migration(s) now?"
                        wire:loading.attr="disabled"
                        wire:target="runPendingMigrations"
                        style="padding:7px 14px; border-radius:7px; background:#6366f1; color:#fff; font-size:12px; font-weight:600; border:none; cursor:pointer; display:flex; align-items:center; gap:5px;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px;"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg>
                        <span wire:loading.remove wire:target="runPendingMigrations">Run {{ $this->pendingCount }} Pending</span>
                        <span wire:loading wire:target="runPendingMigrations">Running…</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>

        @if (!empty($migrationStatus))
        <div class="dbm-card">
            <div class="dbm-scroll">
                <table class="dbm-table">
                    <thead><tr>
                        <th>Migration</th>
                        <th style="width:90px;">Status</th>
                        <th style="width:60px;">Batch</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($migrationStatus as $migration)
                        <tr>
                            <td class="mono">{{ $migration['name'] }}</td>
                            <td>
                                <span class="{{ $migration['status'] === 'ran' ? 'dbm-status-ran' : 'dbm-status-pending' }}">
                                    {{ ucfirst($migration['status']) }}
                                </span>
                            </td>
                            <td style="color:#94a3b8; font-size:12px;">{{ $migration['batch'] ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if (!empty($migrationOutput))
        <div class="dbm-terminal">
            <p class="t-label">Artisan Output</p>
            @foreach ($migrationOutput as $line)
            <p class="{{ str_contains(strtolower($line), 'error') ? 't-error' : (str_contains(strtolower($line), 'migrat') ? 't-running' : 't-ok') }}">{{ $line }}</p>
            @endforeach
        </div>
        @endif
    </div>

</div>

<x-filament-actions::modals />
</x-filament-panels::page>
