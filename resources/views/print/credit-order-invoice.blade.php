<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invoice — {{ $order->order_number }}</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #fff; }
  .page { max-width: 900px; margin: 0 auto; padding: 32px 36px; }

  /* Header */
  .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 20px; border-bottom: 3px solid #1e293b; margin-bottom: 22px; }
  .brand { font-size: 24px; font-weight: 900; color: #0f172a; }
  .brand-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }
  .inv-meta { text-align: right; }
  .inv-number { font-size: 22px; font-weight: 800; color: #1e40af; }
  .inv-label { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; }
  .status-badge { display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; margin-top: 6px; color: white; }

  /* Address block */
  .address-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 22px; }
  .addr-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; }
  .addr-card .label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 8px; }
  .addr-card .name { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 3px; }
  .addr-card .line { font-size: 11px; color: #64748b; margin-top: 2px; }

  /* Items table */
  table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  table.items thead tr { background: #1e293b; color: #fff; }
  table.items thead th { padding: 10px 12px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
  table.items th.r, table.items td.r { text-align: right; }
  table.items tbody tr:nth-child(even) { background: #f8fafc; }
  table.items tbody td { padding: 9px 12px; border-bottom: 1px solid #e2e8f0; font-size: 12px; vertical-align: middle; }
  table.items tfoot td { padding: 8px 12px; font-size: 12px; background: #f1f5f9; }

  /* Totals */
  .totals-wrap { display: flex; justify-content: flex-end; margin-bottom: 24px; }
  .totals-box { min-width: 280px; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
  .totals-row { display: flex; justify-content: space-between; padding: 8px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
  .totals-row:last-child { border-bottom: none; }
  .totals-row.grand { background: #1e293b; color: #fff; font-weight: 800; font-size: 15px; }
  .totals-row.paid   { background: #f0fdf4; color: #166534; }
  .totals-row.bal    { background: #fef2f2; color: #991b1b; font-weight: 700; }
  .totals-row.bal-ok { background: #f0fdf4; color: #166534; font-weight: 700; }

  /* Workflow info */
  .workflow-bar { display: flex; flex-wrap: wrap; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }
  .wf-item { display: flex; flex-direction: column; }
  .wf-label { font-size: 9px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
  .wf-value { font-size: 12px; color: #1f2937; font-weight: 600; margin-top: 2px; }

  /* Footer */
  .inv-footer { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
  .terms h4 { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
  .terms p  { font-size: 11px; color: #64748b; line-height: 1.5; }
  .sig-area { text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; }
  .sig-line { width: 180px; border-top: 1px solid #374151; margin-bottom: 4px; }
  .sig-label { font-size: 10px; color: #6b7280; }
  .generated { font-size: 9px; color: #d1d5db; text-align: center; margin-top: 20px; }

  /* Print bar */
  .print-bar { background: #1e293b; color: #fff; padding: 10px 36px; display: flex; align-items: center; justify-content: space-between; }
  .print-btn { background: #3b82f6; color: #fff; border: none; padding: 7px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }

  @media print {
    .print-bar { display: none; }
    .page { padding: 16px 20px; }
    @page { margin: 10mm; }
  }
</style>
</head>
<body>

<div class="print-bar">
  <span style="font-weight:600;">📄 Invoice — {{ $order->order_number }}</span>
  <div style="display:flex;gap:8px;">
    <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    <button style="background:transparent;color:#94a3b8;border:1px solid #475569;padding:7px 14px;border-radius:6px;font-size:13px;cursor:pointer;" onclick="window.close()">✕ Close</button>
  </div>
</div>

<div class="page">

  {{-- Invoice header --}}
  <div class="invoice-header">
    <div>
      @if($company->logo)
        <img src="{{ asset('storage/' . $company->logo) }}" alt="{{ $company->company_name }}" style="max-height:52px;max-width:200px;object-fit:contain;margin-bottom:4px;">
        <div class="brand-sub">{{ $company->company_name }}</div>
      @else
        <div class="brand">{{ $company->company_name }}</div>
      @endif
      <div class="brand-sub">{{ $company->tagline ?: 'Credit Sales Invoice' }}</div>
      @if($company->phone || $company->email)
        <div class="brand-sub">
          @if($company->phone) {{ $company->phone }} @endif
          @if($company->phone && $company->email) &nbsp;·&nbsp; @endif
          @if($company->email) {{ $company->email }} @endif
        </div>
      @endif
      @if($company->address)
        <div class="brand-sub">{{ $company->address }}{{ $company->city ? ', ' . $company->city : '' }}</div>
      @endif
      @if($company->tax_id)
        <div class="brand-sub">TIN/VAT: {{ $company->tax_id }}</div>
      @endif
    </div>
    <div class="inv-meta">
      <div class="inv-label">Invoice Number</div>
      <div class="inv-number">{{ $order->order_number }}</div>
      <div style="font-size:11px;color:#64748b;margin-top:4px;">Date: {{ $order->order_date?->format('d M Y') }}</div>
      @php
        $statusColors = [
          'shipped' => '#059669','delivered' => '#047857','approved' => '#1d4ed8',
          'in_production' => '#6d28d9','cancelled' => '#b91c1c',
          'ready_to_ship' => '#059669','pending_approval' => '#d97706',
          'escalated' => '#dc2626','draft' => '#6b7280',
        ];
        $sc = $statusColors[$order->status] ?? '#374151';
      @endphp
      <div class="status-badge" style="background:{{ $sc }};">
        {{ strtoupper(\App\Models\CreditOrder::statusLabel($order->status)) }}
      </div>
    </div>
  </div>

  {{-- Address block --}}
  <div class="address-grid">
    <div class="addr-card">
      <div class="label">Billed To</div>
      <div class="name">{{ $order->customer?->name ?? '—' }}</div>
      @if($order->customer?->company_name)
        <div class="line">{{ $order->customer->company_name }}</div>
      @endif
      @if($order->customer?->phone)
        <div class="line">📱 {{ $order->customer->phone }}</div>
      @endif
      @if($order->customer?->email)
        <div class="line">✉️ {{ $order->customer->email }}</div>
      @endif
      @if($order->customer?->address)
        <div class="line">📍 {{ $order->customer->address }}</div>
      @endif
    </div>
    <div class="addr-card">
      <div class="label">Delivery Details</div>
      @if($order->delivery_date)
        <div class="line">📅 Delivery: {{ $order->delivery_date->format('d M Y') }}</div>
      @endif
      @if($order->delivery_address)
        <div class="line">📍 {{ $order->delivery_address }}</div>
      @endif
      @if($order->assignedBranch)
        <div class="line">🏭 Branch: {{ $order->assignedBranch->name }}</div>
      @endif
      @if($order->shipped_at)
        <div class="line">🚚 Shipped: {{ $order->shipped_at->format('d M Y H:i') }}</div>
      @endif
      @if($order->delivered_at)
        <div class="line">✅ Delivered: {{ $order->delivered_at->format('d M Y H:i') }}</div>
      @endif
    </div>
  </div>

  {{-- Workflow bar --}}
  <div class="workflow-bar">
    <div class="wf-item">
      <span class="wf-label">Priority</span>
      <span class="wf-value">{{ \App\Models\CreditOrder::priorityLabel($order->priority ?? 2) }}</span>
    </div>
    <div class="wf-item">
      <span class="wf-label">Approved By</span>
      <span class="wf-value">{{ $order->approvedBy?->name ?? '—' }}</span>
    </div>
    @if($order->approved_at)
    <div class="wf-item">
      <span class="wf-label">Approved On</span>
      <span class="wf-value">{{ $order->approved_at->format('d M Y') }}</span>
    </div>
    @endif
    <div class="wf-item">
      <span class="wf-label">Payment Terms</span>
      <span class="wf-value">{{ strtoupper($order->customer?->payment_terms ?? 'N/A') }}</span>
    </div>
    <div class="wf-item">
      <span class="wf-label">Payment Status</span>
      <span class="wf-value">{{ strtoupper(str_replace('_',' ',$order->payment_status ?? 'unpaid')) }}</span>
    </div>
  </div>

  {{-- Items table --}}
  <table class="items">
    <thead>
      <tr>
        <th>#</th>
        <th>Product / Variant</th>
        <th class="r">Qty</th>
        <th class="r">Unit Price (৳)</th>
        <th class="r">Discount</th>
        <th class="r">Subtotal (৳)</th>
      </tr>
    </thead>
    <tbody>
      @forelse($order->items as $i => $item)
      @php
        $product = $item->product?->name ?? '—';
        $variant = $item->variant?->name  ?? '';
        $dtLabel = match($item->discount_type ?? 'flat') {
          'per_unit' => '৳'.number_format((float)$item->discount,2).'/unit',
          'percent'  => number_format((float)$item->discount,2).'%',
          default    => (float)$item->discount > 0 ? '-৳'.number_format((float)$item->discount,2) : '—',
        };
      @endphp
      <tr>
        <td style="color:#94a3b8;">{{ $i + 1 }}</td>
        <td><strong>{{ $product }}</strong>{{ $variant ? ' — '.$variant : '' }}</td>
        <td class="r">{{ number_format((float)$item->quantity, 2) }}</td>
        <td class="r">{{ number_format((float)$item->unit_price, 2) }}</td>
        <td class="r" style="color:#dc2626;">{{ $dtLabel }}</td>
        <td class="r" style="font-weight:700;color:#065f46;">{{ number_format((float)$item->subtotal, 2) }}</td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;padding:20px;color:#94a3b8;">No items found.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- Totals --}}
  <div class="totals-wrap">
    <div class="totals-box">
      <div class="totals-row"><span>Items Subtotal</span><span>৳ {{ number_format((float)$order->subtotal, 2) }}</span></div>
      <div class="totals-row"><span style="color:#dc2626;">Order Discount</span><span style="color:#dc2626;">- ৳ {{ number_format((float)$order->discount, 2) }}</span></div>
      <div class="totals-row"><span>Tax / VAT</span><span>+ ৳ {{ number_format((float)$order->tax, 2) }}</span></div>
      <div class="totals-row grand"><span>TOTAL DUE</span><span>৳ {{ number_format((float)$order->total, 2) }}</span></div>
      <div class="totals-row paid"><span>Paid</span><span>৳ {{ number_format((float)$order->paid_amount, 2) }}</span></div>
      @if((float)$order->balance > 0)
      <div class="totals-row bal"><span>Balance Due</span><span>৳ {{ number_format((float)$order->balance, 2) }}</span></div>
      @else
      <div class="totals-row bal-ok"><span>Balance Due</span><span>✓ Fully Paid</span></div>
      @endif
    </div>
  </div>

  {{-- Footer --}}
  <div class="inv-footer">
    <div class="terms">
      <h4>Terms & Conditions</h4>
      <p>Payment is due as per agreed credit terms. Please reference invoice number {{ $order->order_number }} in all correspondence and payments.</p>
      @if($order->notes)
        <p style="margin-top:8px;"><strong>Notes:</strong> {{ $order->notes }}</p>
      @endif
    </div>
    <div class="sig-area">
      <div class="sig-line"></div>
      <div class="sig-label">Authorised Signatory</div>
    </div>
  </div>

  <div class="generated">
    Computer-generated invoice · {{ $company->company_name }} · {{ now()->format('d M Y H:i:s') }}
  </div>

</div>
</body>
</html>
