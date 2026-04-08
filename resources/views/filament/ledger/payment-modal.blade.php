<style>
.pm-wrap{font-family:inherit;font-size:13px;color:inherit}
.pm-header{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:14px;margin-bottom:14px;border-bottom:1px solid #e5e7eb}
.dark .pm-header{border-color:rgba(255,255,255,.12)}
.pm-title{font-size:20px;font-weight:800;color:#059669;letter-spacing:-.3px}
.dark .pm-title{color:#34d399}
.pm-subtitle{font-size:12px;color:#9ca3af;margin-top:2px}
.pm-order-num{font-size:15px;font-weight:700;color:#d97706;text-align:right}
.dark .pm-order-num{color:#fbbf24}
.pm-date{font-size:11px;color:#9ca3af;text-align:right;margin-top:2px}
.pm-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
.pm-card{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px}
.dark .pm-card{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1)}
.pm-card-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:6px}
.pm-card-value{font-size:14px;font-weight:600;color:#111827}
.dark .pm-card-value{color:#f9fafb}
.pm-desc-box{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;margin-bottom:14px}
.dark .pm-desc-box{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1)}
.pm-desc-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:6px}
.pm-desc-text{font-size:13px;color:#374151}
.dark .pm-desc-text{color:#d1d5db}
.pm-footer{display:flex;justify-content:flex-end;padding-top:12px;border-top:1px solid #e5e7eb}
.dark .pm-footer{border-color:rgba(255,255,255,.12)}
.pm-btn{display:inline-flex;align-items:center;gap:6px;background:#1e293b;color:#fff;padding:8px 18px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600}
.pm-btn:hover{background:#334155}
.dark .pm-btn{background:#f9fafb;color:#111827}
</style>

@php $printUrl = route('print.credit-order', $order->id); @endphp

<div class="pm-wrap">

    <div class="pm-header">
        <div>
            <div class="pm-title">PAYMENT RECEIPT</div>
            <div class="pm-subtitle">Orchestra ERP</div>
        </div>
        <div>
            <div class="pm-order-num">{{ $order->order_number }}</div>
            <div class="pm-date">{{ $entry->date?->format('d M Y') }}</div>
        </div>
    </div>

    <div class="pm-grid">
        <div class="pm-card">
            <div class="pm-card-label">Customer</div>
            <div class="pm-card-value">{{ $order->customer?->name ?? '—' }}</div>
        </div>
        <div class="pm-card">
            <div class="pm-card-label">Payment Amount</div>
            <div style="font-size:22px;font-weight:800;color:#059669;">৳ {{ number_format((float)$entry->credit, 2) }}</div>
        </div>
        <div class="pm-card">
            <div class="pm-card-label">Order Total</div>
            <div class="pm-card-value">৳ {{ number_format((float)$order->total, 2) }}</div>
        </div>
        <div class="pm-card">
            <div class="pm-card-label">Remaining Balance</div>
            <div class="pm-card-value" style="color:{{ (float)$entry->balance > 0 ? '#dc2626' : '#059669' }}">
                ৳ {{ number_format((float)$entry->balance, 2) }}
            </div>
        </div>
    </div>

    <div class="pm-desc-box">
        <div class="pm-desc-label">Description</div>
        <div class="pm-desc-text">{{ $entry->description }}</div>
    </div>

    <div class="pm-footer">
        <a href="{{ $printUrl }}" target="_blank" class="pm-btn">🖨️ Print Invoice</a>
    </div>

</div>
