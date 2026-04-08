<style>
.gm-wrap{font-family:inherit;font-size:13px}
.gm-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
.gm-card{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px}
.dark .gm-card{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1)}
.gm-card-span{grid-column:span 2}
.gm-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:5px}
.gm-value{font-size:14px;font-weight:600;color:#111827}
.dark .gm-value{color:#f9fafb}
</style>

<div class="gm-wrap">
    <div class="gm-grid">
        <div class="gm-card">
            <div class="gm-label">Date</div>
            <div class="gm-value">{{ $record->date?->format('d M Y') ?? '—' }}</div>
        </div>
        <div class="gm-card">
            <div class="gm-label">Type</div>
            <div class="gm-value">{{ $record->typeLabel() }}</div>
        </div>
        <div class="gm-card">
            <div class="gm-label">Debit</div>
            <div class="gm-value" style="color:#dc2626">৳ {{ number_format((float)$record->debit, 2) }}</div>
        </div>
        <div class="gm-card">
            <div class="gm-label">Credit</div>
            <div class="gm-value" style="color:#059669">৳ {{ number_format((float)$record->credit, 2) }}</div>
        </div>
        <div class="gm-card gm-card-span">
            <div class="gm-label">Running Balance</div>
            <div class="gm-value">৳ {{ number_format((float)$record->balance, 2) }}</div>
        </div>
        <div class="gm-card gm-card-span">
            <div class="gm-label">Description</div>
            <div class="gm-value" style="font-weight:400">{{ $record->description }}</div>
        </div>
    </div>
</div>
