<style>
.lm-wrap{font-family:inherit;font-size:13px;color:inherit}
.lm-header{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:14px;margin-bottom:14px;border-bottom:1px solid #e5e7eb}
.dark .lm-header{border-color:rgba(255,255,255,.12)}
.lm-title{font-size:20px;font-weight:800;letter-spacing:-.3px;color:#059669}
.dark .lm-title{color:#34d399}
.lm-subtitle{font-size:12px;color:#9ca3af;margin-top:2px}
.lm-date{font-size:12px;color:#6b7280;text-align:right;margin-top:4px}
.dark .lm-date{color:#9ca3af}
.lm-ref{font-size:11px;color:#9ca3af;text-align:right;margin-top:2px}
.lm-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
.lm-card{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px}
.dark .lm-card{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1)}
.lm-card-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:6px}
.lm-card-title{font-size:14px;font-weight:700;color:#111827}
.dark .lm-card-title{color:#f9fafb}
.lm-card-sub{font-size:12px;color:#6b7280;margin-top:2px}
.dark .lm-card-sub{color:#9ca3af}
.lm-amount-large{font-size:26px;font-weight:800;color:#059669}
.dark .lm-amount-large{color:#34d399}
.lm-table{width:100%;border-collapse:collapse;margin-bottom:14px;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb}
.dark .lm-table{border-color:rgba(255,255,255,.1)}
.lm-thead tr{background:#1e293b}
.lm-thead th{padding:9px 12px;font-size:11px;font-weight:700;color:#fff;text-align:left}
.lm-thead th:not(:first-child){text-align:right}
.lm-tbody tr{border-top:1px solid #f3f4f6}
.dark .lm-tbody tr{border-color:rgba(255,255,255,.06)}
.lm-tbody tr:nth-child(even){background:#f9fafb}
.dark .lm-tbody tr:nth-child(even){background:rgba(255,255,255,.03)}
.lm-tbody td{padding:8px 12px;font-size:12px;color:#374151}
.dark .lm-tbody td{color:#d1d5db}
.lm-td-right{text-align:right}
.lm-td-green{text-align:right;font-weight:700;color:#059669}
.dark .lm-td-green{color:#34d399}
.lm-no-alloc{padding:14px;text-align:center;font-size:12px;color:#9ca3af;background:#f9fafb;border-radius:8px;margin-bottom:14px}
.dark .lm-no-alloc{background:rgba(255,255,255,.03)}
.lm-notes-card{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;margin-bottom:14px}
.dark .lm-notes-card{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1)}
.lm-notes-text{font-size:12px;color:#374151;margin-top:4px}
.dark .lm-notes-text{color:#d1d5db}
</style>

<div class="lm-wrap">

    {{-- Header --}}
    <div class="lm-header">
        <div>
            <div class="lm-title">PAYMENT RECEIPT</div>
            <div class="lm-subtitle">Orchestra ERP</div>
        </div>
        <div>
            <div class="lm-date">{{ $payment->payment_date?->format('d M Y') }}</div>
            @if($payment->reference)
                <div class="lm-ref">Ref: {{ $payment->reference }}</div>
            @endif
        </div>
    </div>

    {{-- Info grid --}}
    <div class="lm-grid">
        <div class="lm-card">
            <div class="lm-card-label">Customer</div>
            <div class="lm-card-title">{{ $payment->customer?->name ?? '—' }}</div>
            @if($payment->customer?->company_name)
                <div class="lm-card-sub">{{ $payment->customer->company_name }}</div>
            @endif
            @if($payment->customer?->phone)
                <div class="lm-card-sub">{{ $payment->customer->phone }}</div>
            @endif
        </div>
        <div class="lm-card">
            <div class="lm-card-label">Amount Received</div>
            <div class="lm-amount-large">৳ {{ number_format((float)$payment->amount, 2) }}</div>
        </div>
        <div class="lm-card">
            <div class="lm-card-label">Payment Method</div>
            <div class="lm-card-title">{{ \App\Models\CustomerPayment::methodLabel($payment->payment_method) }}</div>
        </div>
        <div class="lm-card">
            <div class="lm-card-label">Status</div>
            <div class="lm-card-title" style="color:{{ $payment->status === 'confirmed' ? '#059669' : '#dc2626' }}">
                {{ ucfirst($payment->status) }}
            </div>
        </div>
    </div>

    {{-- Allocations table --}}
    @if($payment->allocations->count() > 0)
        <table class="lm-table">
            <thead class="lm-thead">
                <tr>
                    <th>Order #</th>
                    <th>Order Total</th>
                    <th>Allocated</th>
                </tr>
            </thead>
            <tbody class="lm-tbody">
                @foreach($payment->allocations as $alloc)
                    <tr>
                        <td>{{ $alloc->order?->order_number ?? '—' }}</td>
                        <td class="lm-td-right">৳ {{ number_format((float)($alloc->order?->total ?? 0), 2) }}</td>
                        <td class="lm-td-green">৳ {{ number_format((float)$alloc->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="lm-no-alloc">No order allocations recorded for this payment.</div>
    @endif

    {{-- Notes --}}
    @if($payment->notes)
        <div class="lm-notes-card">
            <div class="lm-card-label">Notes</div>
            <div class="lm-notes-text">{{ $payment->notes }}</div>
        </div>
    @endif

</div>
