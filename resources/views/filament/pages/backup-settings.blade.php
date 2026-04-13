<x-filament-panels::page>
<style>
    .bs-status { display:flex; align-items:center; gap:1rem;
        padding:1rem 1.25rem; border-radius:10px; margin-bottom:1rem;
        border:1px solid; }
    .bs-status-success { background:#f0fdf4; border-color:#86efac; color:#166534; }
    .bs-status-failed  { background:#fef2f2; border-color:#fca5a5; color:#991b1b; }
    .bs-status-none    { background:#f9fafb; border-color:#e5e7eb; color:#6b7280; }
    .dark .bs-status-success { background:#052e16; border-color:#166534; color:#86efac; }
    .dark .bs-status-failed  { background:#450a0a; border-color:#991b1b; color:#fca5a5; }
    .dark .bs-status-none    { background:#1f2937; border-color:#374151; color:#9ca3af; }
    .bs-status-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
    .bs-status-success .bs-status-dot { background:#22c55e; }
    .bs-status-failed  .bs-status-dot { background:#ef4444; }
    .bs-status-none    .bs-status-dot { background:#d1d5db; }
    .bs-status-info { display:flex; flex-direction:column; gap:.2rem; }
    .bs-status-title { font-weight:700; font-size:.9rem; }
    .bs-status-detail { font-size:.8rem; opacity:.8; }
</style>

@php
    $cfg = \App\Models\BackupConfiguration::get();
    $statusClass = match($cfg->last_backup_status) {
        'success' => 'bs-status-success',
        'failed'  => 'bs-status-failed',
        default   => 'bs-status-none',
    };
@endphp

{{-- Last backup status banner --}}
<div class="bs-status {{ $statusClass }}">
    <div class="bs-status-dot"></div>
    <div class="bs-status-info">
        <div class="bs-status-title">
            @if($cfg->last_backup_status === 'success')
                Last backup: success
            @elseif($cfg->last_backup_status === 'failed')
                Last backup: failed
            @else
                No backup has run yet
            @endif
        </div>
        @if($cfg->last_backup_at)
            <div class="bs-status-detail">
                {{ $cfg->last_backup_at->diffForHumans() }} &mdash; {{ $cfg->last_backup_message }}
            </div>
        @endif
        @if(!$cfg->google_credentials || !$cfg->google_folder_id)
            <div class="bs-status-detail">Google Drive not yet configured. Fill in the credentials below to enable backups.</div>
        @endif
    </div>
</div>

<form wire:submit="save">
    {{ $this->form }}

    <div class="mt-6 flex flex-wrap gap-3">
        @foreach ($this->getFormActions() as $action)
            {{ $action }}
        @endforeach
    </div>
</form>

<x-filament-actions::modals />
</x-filament-panels::page>
