<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payment Receipt — {{ $payment->customer?->name }}</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #fff; }
  .page { max-width: 780px; margin: 0 auto; padding: 32px 36px; }

  /* Print bar */
  .print-bar { background: #1e293b; color: #fff; padding: 10px 36px; display: flex; align-items: center; justify-content: space-between; }
  .print-btn { background: #3b82f6; color: #fff; border: none; padding: 7px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
  .close-btn { background: transparent; color: #94a3b8; border: 1px solid #475569; padding: 7px 14px; border-radius: 6px; font-size: 13px; cursor: pointer; }

  /* Header */
  .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 18px; border-bottom: 3px solid #1e293b; margin-bottom: 22px; }
  .brand-name { font-size: 22px; font-weight: 900; color: #0f172a; }
  .brand-sub  { font-size: 11px; color: #94a3b8; margin-top: 2px; }
  .receipt-meta { text-align: right; }
  .receipt-title { font-size: 20px; font-weight: 800; color: #059669; }
  .receipt-num   { font-size: 13px; color: #374151; font-weight: 700; margin-top: 4px; }
  .receipt-date  { font-size: 11px; color: #6b7280; margin-top: 2px; }

  /* Amount hero */
  .amount-hero { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 2px solid #6ee7b7; border-radius: 14px; padding: 22px 28px; text-align: center; margin-bottom: 22px; }
  .amount-hero .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #059669; margin-bottom: 6px; }
  .amount-hero .value { font-size: 38px; font-weight: 900; color: #065f46; line-height: 1; }
  .amount-hero .method-badge { display: inline-block; margin-top: 10px; background: #065f46; color: #fff; padding: 4px 14px; border-radius: 999px; font-size: 12px; font-weight: 600; }

  /* Info grid */
  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 22px; }
  .info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; }
  .info-card .ic-label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 6px; }
  .info-card .ic-value { font-size: 13px; font-weight: 600; color: #0f172a; }
  .info-card .ic-sub   { font-size: 11px; color: #64748b; margin-top: 2px; }

  /* Allocations table */
  .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; margin-bottom: 8px; margin-top: 20px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  thead tr { background: #1e293b; color: #fff; }
  thead th { padding: 9px 12px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
  th.r, td.r { text-align: right; }
  tbody tr:nth-child(even) { background: #f8fafc; }
  tbody td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; font-size: 12px; vertical-align: middle; }
  tfoot td { padding: 9px 12px; background: #f1f5f9; font-weight: 700; font-size: 12px; }

  /* Footer */
  .footer { margin-top: 28px; padding-top: 14px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-end; }
  .footer .note { font-size: 10px; color: #94a3b8; line-height: 1.6; }
  .sig-block { text-align: center; }
  .sig-line  { width: 160px; border-top: 1px solid #374151; margin-bottom: 4px; }
  .sig-label { font-size: 10px; color: #6b7280; }

  /* Stamp */
  .stamp { display: inline-block; border: 3px solid #059669; color: #059669; font-size: 16px; font-weight: 900; letter-spacing: .1em; padding: 6px 18px; border-radius: 6px; transform: rotate(-6deg); margin-top: 8px; text-transform: uppercase; }

  @media print {
    .print-bar { display: none; }
    .page { padding: 16px 20px; }
    @page { margin: 10mm; }
  }
</style>
</head>
<body>

<div class="print-bar">
  <span style="font-weight:600;">🧾 Payment Receipt — {{ $payment->customer?->name }}</span>
  <div style="display:flex;gap:8px;">
    <button class="print-btn" onclick="window.print()">🖨️ Print / Save PDF</button>
    <button class="close-btn" onclick="window.close()">✕ Close</button>
  </div>
</div>

<div class="page">

  {{-- Header --}}
  <div class="header">
    <div>
      @if($company->logo)
        <img src="{{ asset('storage/' . $company->logo) }}" alt="{{ $company->company_name }}" style="max-height:48px;max-width:180px;object-fit:contain;margin-bottom:4px;">
        <div class="brand-sub">{{ $company->company_name }}</div>
      @else
        <div class="brand-name">{{ $company->company_name }}</div>
      @endif
      @if($company->tagline)
        <div class="brand-sub">{{ $company->tagline }}</div>
      @endif
      @if($company->address)
        <div class="brand-sub">{{ $company->address }}{{ $company->city ? ', '.$company->city : '' }}</div>
      @endif
      @if($company->phone || $company->email)
        <div class="brand-sub">
          @if($company->phone) {{ $company->phone }} @endif
          @if($company->phone && $company->email) &nbsp;·&nbsp; @endif
          @if($company->email) {{ $company->email }} @endif
        </div>
      @endif
    </div>
    <div class="receipt-meta">
      <div class="receipt-title">PAYMENT RECEIPT</div>
      <div class="receipt-num">Receipt #{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
      <div class="receipt-date">Date: {{ $payment->payment_date?->format('d M Y') }}</div>
      <div class="receipt-date">Printed: {{ now()->format('d M Y, H:i') }}</div>
    </div>
  </div>

  {{-- Amount hero --}}
  <div class="amount-hero">
    <div class="label">Amount Received</div>
    <div class="value">৳ {{ number_format((float) $payment->amount, 2) }}</div>
    <div class="method-badge">{{ \App\Models\CustomerPayment::methodLabel($payment->payment_method) }}</div>
  </div>

  {{-- Info grid --}}
  <div class="info-grid">
    <div class="info-card">
      <div class="ic-label">Received From</div>
      <div class="ic-value">{{ $payment->customer?->name ?? '—' }}</div>
      @if($payment->customer?->company_name)
        <div class="ic-sub">{{ $payment->customer->company_name }}</div>
      @endif
      @if($payment->customer?->phone)
        <div class="ic-sub">📱 {{ $payment->customer->phone }}</div>
      @endif
      @if($payment->customer?->address)
        <div class="ic-sub">{{ $payment->customer->address }}</div>
      @endif
    </div>

    <div class="info-card">
      <div class="ic-label">Payment Details</div>
      <div class="ic-value">{{ \App\Models\CustomerPayment::methodLabel($payment->payment_method) }}</div>
      @if($payment->reference)
        <div class="ic-sub">Ref: {{ $payment->reference }}</div>
      @endif
      @if($payment->bankAccount)
        <div class="ic-sub">Bank: {{ $payment->bankAccount->bank_name }} — {{ $payment->bankAccount->account_name }}</div>
      @endif
      @if($payment->branch)
        <div class="ic-sub">Branch: {{ $payment->branch->name }}</div>
      @endif
      @if($payment->notes)
        <div class="ic-sub" style="margin-top:4px;font-style:italic;">{{ $payment->notes }}</div>
      @endif
    </div>
  </div>

  {{-- Allocations --}}
  @if($payment->allocations->count() > 0)
    <div class="section-title">Applied Against Invoices</div>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Invoice / Order No.</th>
          <th class="r">Invoice Total (৳)</th>
          <th class="r">Previously Paid (৳)</th>
          <th class="r">Applied (৳)</th>
          <th class="r">Remaining (৳)</th>
        </tr>
      </thead>
      <tbody>
        @foreach($payment->allocations as $i => $alloc)
          @php
            $order      = $alloc->order;
            $prevPaid   = (float)($order?->paid_amount ?? 0) - (float)$alloc->amount;
            $remaining  = max(0, (float)($order?->balance ?? 0));
          @endphp
          <tr>
            <td style="color:#94a3b8;font-size:10px;">{{ $i + 1 }}</td>
            <td style="font-weight:600;">{{ $order?->order_number ?? '—' }}</td>
            <td class="r">{{ number_format((float)($order?->total ?? 0), 2) }}</td>
            <td class="r" style="color:#6b7280;">{{ number_format(max(0,$prevPaid), 2) }}</td>
            <td class="r" style="color:#059669;font-weight:700;">{{ number_format((float)$alloc->amount, 2) }}</td>
            <td class="r" style="{{ $remaining <= 0 ? 'color:#059669;' : 'color:#dc2626;' }}font-weight:600;">
              {{ $remaining <= 0 ? '✓ Settled' : number_format($remaining, 2) }}
            </td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4">Total Applied</td>
          <td class="r">{{ number_format((float)$payment->allocations->sum('amount'), 2) }}</td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  @else
    <div class="section-title">Allocation</div>
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;font-size:12px;color:#6b7280;font-style:italic;">
      This payment was recorded as an unallocated / previous balance payment — not linked to a specific invoice.
    </div>
  @endif

  {{-- Footer --}}
  <div class="footer">
    <div class="note">
      <div>This is a computer-generated receipt and serves as official proof of payment.</div>
      <div>Generated by {{ $company->company_name }} · {{ now()->format('d M Y H:i:s') }}</div>
      @if($company->tax_id)
        <div>TIN/VAT: {{ $company->tax_id }}</div>
      @endif
    </div>
    <div style="text-align:right;">
      <div class="stamp">RECEIVED</div>
      <div style="margin-top:16px;" class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-label">Authorised Signatory</div>
      </div>
    </div>
  </div>

</div>
</body>
</html>
