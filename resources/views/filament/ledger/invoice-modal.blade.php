<style>
.lm-wrap{font-family:inherit;font-size:13px;color:inherit}
.lm-header{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:14px;margin-bottom:14px;border-bottom:1px solid #e5e7eb}
.dark .lm-header{border-color:rgba(255,255,255,.12)}
.lm-title{font-size:20px;font-weight:800;color:#111827;letter-spacing:-.3px}
.dark .lm-title{color:#f9fafb}
.lm-subtitle{font-size:12px;color:#9ca3af;margin-top:2px}
.lm-order-num{font-size:15px;font-weight:700;color:#d97706;text-align:right}
.dark .lm-order-num{color:#fbbf24}
.lm-date{font-size:11px;color:#9ca3af;text-align:right;margin-top:2px}
.lm-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:700;color:#fff;margin-top:4px}
.lm-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
.lm-card{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px}
.dark .lm-card{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1)}
.lm-card-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:6px}
.lm-card-title{font-size:14px;font-weight:700;color:#111827}
.dark .lm-card-title{color:#f9fafb}
.lm-card-sub{font-size:12px;color:#6b7280;margin-top:2px}
.dark .lm-card-sub{color:#9ca3af}
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
.lm-td-red{text-align:right;color:#dc2626}
.dark .lm-td-red{color:#f87171}
.lm-td-green{text-align:right;font-weight:700;color:#059669}
.dark .lm-td-green{color:#34d399}
.lm-totals{display:flex;justify-content:flex-end;margin-bottom:14px}
.lm-totals-box{min-width:240px}
.lm-total-row{display:flex;justify-content:space-between;padding:3px 0;font-size:12px;color:#6b7280}
.dark .lm-total-row{color:#9ca3af}
.lm-total-divider{border:none;border-top:1px solid #e5e7eb;margin:6px 0}
.dark .lm-total-divider{border-color:rgba(255,255,255,.12)}
.lm-total-bold{display:flex;justify-content:space-between;padding:3px 0;font-size:13px;font-weight:700;color:#111827}
.dark .lm-total-bold{color:#f9fafb}
.lm-total-paid{display:flex;justify-content:space-between;padding:3px 0;font-size:12px;color:#059669}
.dark .lm-total-paid{color:#34d399}
.lm-total-balance-red{display:flex;justify-content:space-between;padding:3px 0;font-size:13px;font-weight:700;color:#dc2626}
.dark .lm-total-balance-red{color:#f87171}
.lm-total-balance-green{display:flex;justify-content:space-between;padding:3px 0;font-size:13px;font-weight:700;color:#059669}
.dark .lm-total-balance-green{color:#34d399}
.lm-footer{display:flex;justify-content:flex-end;padding-top:12px;border-top:1px solid #e5e7eb}
.dark .lm-footer{border-color:rgba(255,255,255,.12)}
.lm-btn{display:inline-flex;align-items:center;gap:6px;background:#1e293b;color:#fff;padding:8px 18px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;transition:background .15s}
.lm-btn:hover{background:#334155}
.dark .lm-btn{background:#f9fafb;color:#111827}
.dark .lm-btn:hover{background:#e5e7eb}
</style>

@php
    $statusColors = [
        'shipped'          => '#059669',
        'delivered'        => '#16a34a',
        'approved'         => '#1d4ed8',
        'in_production'    => '#7c3aed',
        'cancelled'        => '#dc2626',
        'ready_to_ship'    => '#0d9488',
        'pending_approval' => '#d97706',
        'escalated'        => '#ea580c',
        'draft'            => '#6b7280',
    ];
    $statusColor = $statusColors[$order->status] ?? '#6b7280';
    $printUrl    = route('print.credit-order', $order->id);
@endphp

<div class="lm-wrap">

    {{-- Header --}}
    <div class="lm-header">
        <div>
            <div class="lm-title">CREDIT INVOICE</div>
            <div class="lm-subtitle">Orchestra ERP</div>
        </div>
        <div>
            <div class="lm-order-num">{{ $order->order_number }}</div>
            <div class="lm-date">Date: {{ $order->order_date?->format('d M Y') }}</div>
            <span class="lm-badge" style="background:{{ $statusColor }}">
                {{ strtoupper(\App\Models\CreditOrder::statusLabel($order->status)) }}
            </span>
        </div>
    </div>

    {{-- Billed To / Delivery grid --}}
    <div class="lm-grid">
        <div class="lm-card">
            <div class="lm-card-label">Billed To</div>
            <div class="lm-card-title">{{ $order->customer?->name ?? '—' }}</div>
            @if($order->customer?->company_name)
                <div class="lm-card-sub">{{ $order->customer->company_name }}</div>
            @endif
            @if($order->customer?->phone)
                <div class="lm-card-sub">📱 {{ $order->customer->phone }}</div>
            @endif
        </div>
        <div class="lm-card">
            <div class="lm-card-label">Delivery</div>
            @if($order->delivery_date)
                <div class="lm-card-sub">📅 {{ $order->delivery_date->format('d M Y') }}</div>
            @endif
            @if($order->delivery_address)
                <div class="lm-card-sub">📍 {{ $order->delivery_address }}</div>
            @endif
            @if($order->assignedBranch)
                <div class="lm-card-sub">🏭 {{ $order->assignedBranch->name }}</div>
            @endif
            <div class="lm-card-sub">👤 Approved: {{ $order->approvedBy?->name ?? '—' }}</div>
        </div>
    </div>

    {{-- Items table --}}
    <table class="lm-table">
        <thead class="lm-thead">
            <tr>
                <th>Product / Variant</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Discount</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody class="lm-tbody">
            @foreach($order->items as $item)
                @php
                    $discLabel = match($item->discount_type ?? 'flat') {
                        'per_unit' => '৳' . number_format((float)$item->discount, 2) . '/unit',
                        'percent'  => number_format((float)$item->discount, 2) . '%',
                        default    => $item->discount > 0 ? '-৳' . number_format((float)$item->discount, 2) : '—',
                    };
                @endphp
                <tr>
                    <td>
                        {{ $item->product?->name ?? '—' }}
                        @if($item->variant?->name)
                            <span style="color:#9ca3af"> — {{ $item->variant->name }}</span>
                        @endif
                    </td>
                    <td class="lm-td-right">{{ number_format((float)$item->quantity, 2) }}</td>
                    <td class="lm-td-right">৳ {{ number_format((float)$item->unit_price, 2) }}</td>
                    <td class="lm-td-red">{{ $discLabel }}</td>
                    <td class="lm-td-green">৳ {{ number_format((float)$item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="lm-totals">
        <div class="lm-totals-box">
            <div class="lm-total-row"><span>Items Subtotal</span><span>৳ {{ number_format((float)$order->subtotal, 2) }}</span></div>
            <div class="lm-total-row" style="color:#dc2626"><span>Order Discount</span><span>- ৳ {{ number_format((float)$order->discount, 2) }}</span></div>
            <div class="lm-total-row"><span>Tax / VAT</span><span>+ ৳ {{ number_format((float)$order->tax, 2) }}</span></div>
            <hr class="lm-total-divider">
            <div class="lm-total-bold"><span>TOTAL DUE</span><span>৳ {{ number_format((float)$order->total, 2) }}</span></div>
            <div class="lm-total-paid"><span>Paid</span><span>৳ {{ number_format((float)$order->paid_amount, 2) }}</span></div>
            @if((float)$order->balance > 0)
                <div class="lm-total-balance-red"><span>Balance Due</span><span>৳ {{ number_format((float)$order->balance, 2) }}</span></div>
            @else
                <div class="lm-total-balance-green"><span>Balance Due</span><span>৳ {{ number_format((float)$order->balance, 2) }}</span></div>
            @endif
        </div>
    </div>

    {{-- Print button --}}
    <div class="lm-footer">
        <a href="{{ $printUrl }}" target="_blank" class="lm-btn">🖨️ Print Invoice</a>
    </div>

</div>
