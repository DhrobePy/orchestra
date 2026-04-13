<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Account Statement — {{ $customer->name }}</title>
<style>
  /* ── Template CSS variables ── */
  :root {
    --tpl-primary:   #1e3a5f;
    --tpl-accent:    #3b82f6;
    --tpl-text:      #111827;
    --tpl-border:    #e5e7eb;
    --tpl-header-bg: #1e3a5f;
    --tpl-header-fg: #ffffff;
    --tpl-font:      'Segoe UI', Arial, sans-serif;
  }
  @isset($tplCss) {!! $tplCss !!} @endisset

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: var(--tpl-font); font-size: 12px; color: var(--tpl-text); background: #fff; }
  .page { max-width: 900px; margin: 0 auto; padding: 32px 36px; }

  /* Header */
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-bottom: 18px; border-bottom: 3px solid var(--tpl-header-bg); }
  .header-left .company { font-size: 22px; font-weight: 800; color: var(--tpl-primary); letter-spacing: -.5px; }
  .header-left .tagline { font-size: 11px; color: #94a3b8; margin-top: 2px; }
  .header-right { text-align: right; }
  .header-right .doc-title { font-size: 18px; font-weight: 700; color: var(--tpl-primary); }
  .header-right .period { font-size: 11px; color: #6b7280; margin-top: 3px; }

  /* Customer info */
  .customer-block { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
  .info-card { background: #f8fafc; border: 1px solid var(--tpl-border); border-radius: 8px; padding: 12px 16px; }
  .info-card .label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 6px; }
  .info-card .value { font-size: 13px; font-weight: 600; color: var(--tpl-primary); }
  .info-card .sub   { font-size: 11px; color: #64748b; margin-top: 2px; }

  /* Summary cards — semantic colours kept intentionally */
  .summary-row { display: flex; gap: 12px; margin-bottom: 20px; }
  .summary-card { flex: 1; border-radius: 10px; padding: 12px 14px; text-align: center; }
  .summary-card .s-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 4px; }
  .summary-card .s-value { font-size: 17px; font-weight: 800; }
  .card-primary { background: color-mix(in srgb, var(--tpl-primary) 10%, white); border: 1px solid color-mix(in srgb, var(--tpl-primary) 25%, white); }
  .card-primary .s-label { color: var(--tpl-primary); } .card-primary .s-value { color: var(--tpl-primary); }
  .card-red    { background: #fef2f2; border: 1px solid #fecaca; }
  .card-red .s-label  { color: #ef4444; } .card-red .s-value  { color: #dc2626; }
  .card-green  { background: #f0fdf4; border: 1px solid #bbf7d0; }
  .card-green .s-label { color: #22c55e; } .card-green .s-value { color: #16a34a; }
  .card-gray   { background: #f9fafb; border: 1px solid #e5e7eb; }
  .card-gray .s-label  { color: #6b7280; } .card-gray .s-value  { color: #374151; }

  /* Table */
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  thead tr { background: var(--tpl-header-bg); color: var(--tpl-header-fg); }
  thead th { padding: 10px 12px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }
  th.num, td.num { text-align: right; }
  tbody tr:nth-child(even) { background: #f8fafc; }
  tbody tr:hover { background: #f1f5f9; }
  tbody td { padding: 9px 12px; border-bottom: 1px solid var(--tpl-border); font-size: 12px; vertical-align: middle; }
  td.debit  { color: #dc2626; font-weight: 600; }
  td.credit { color: #16a34a; font-weight: 600; }
  td.balance-pos { color: #dc2626; font-weight: 700; }
  td.balance-neg { color: #16a34a; font-weight: 700; }
  tfoot tr { background: var(--tpl-header-bg); color: var(--tpl-header-fg); }
  tfoot td { padding: 10px 12px; font-weight: 700; font-size: 12px; }

  /* Footer */
  .footer { margin-top: 28px; padding-top: 14px; border-top: 1px solid var(--tpl-border); display: flex; justify-content: space-between; align-items: flex-end; }
  .footer .note { font-size: 10px; color: #94a3b8; }
  .footer .sig-block { text-align: center; }
  .footer .sig-line { width: 160px; border-top: 1px solid #374151; margin-bottom: 4px; }
  .footer .sig-label { font-size: 10px; color: #6b7280; }

  /* Print controls */
  .print-bar { background: var(--tpl-header-bg); color: var(--tpl-header-fg); padding: 10px 36px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 0; }
  .print-bar .print-btn { background: var(--tpl-accent); color: #fff; border: none; padding: 7px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
  .print-bar .close-btn { background: transparent; color: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.3); padding: 7px 14px; border-radius: 6px; font-size: 13px; cursor: pointer; }

  @media print {
    .print-bar { display: none; }
    .page { padding: 16px 20px; }
    body { font-size: 11px; }
    @page { margin: 10mm; }
  }
</style>
</head>
<body>

<div class="print-bar">
  <span style="font-weight:600;">📄 Account Statement — {{ $customer->name }}</span>
  <div style="display:flex;gap:8px;">
    <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    <button class="close-btn" onclick="window.close()">✕ Close</button>
  </div>
</div>

<div class="page">

  {{-- Header --}}
  <div class="header">
    <div class="header-left">
      @if($company->logo)
        <img src="{{ asset('storage/' . $company->logo) }}" alt="{{ $company->company_name }}" style="max-height:52px;max-width:200px;object-fit:contain;margin-bottom:4px;">
      @else
        <div class="company">{{ $company->company_name }}</div>
      @endif
      <div class="tagline">{{ $company->tagline ?: 'Account Ledger Statement' }}</div>
      @if($company->phone || $company->email)
        <div class="tagline" style="margin-top:2px;">
          @if($company->phone) {{ $company->phone }} @endif
          @if($company->phone && $company->email) &nbsp;·&nbsp; @endif
          @if($company->email) {{ $company->email }} @endif
        </div>
      @endif
    </div>
    <div class="header-right">
      <div class="doc-title">ACCOUNT STATEMENT</div>
      <div class="period">Period: {{ $period }}</div>
      <div class="period">Printed: {{ now()->format('d M Y, H:i') }}</div>
    </div>
  </div>

  {{-- Customer Info --}}
  <div class="customer-block">
    <div class="info-card">
      <div class="label">Customer</div>
      <div class="value">{{ $customer->name }}</div>
      @if($customer->company_name)
      <div class="sub">{{ $customer->company_name }}</div>
      @endif
      @if($customer->phone)
      <div class="sub">📱 {{ $customer->phone }}</div>
      @endif
      @if($customer->address)
      <div class="sub">{{ $customer->address }}</div>
      @endif
    </div>
    <div class="info-card">
      <div class="label">Account Details</div>
      <div class="value">Account #{{ str_pad($customer->id, 6, '0', STR_PAD_LEFT) }}</div>
      <div class="sub">Payment Terms: {{ strtoupper($customer->payment_terms ?? 'N/A') }}</div>
      <div class="sub">Credit Limit: ৳ {{ number_format((float)$customer->credit_limit, 2) }}</div>
      <div class="sub">Status: {{ $customer->is_active ? 'Active' : 'Inactive' }}</div>
    </div>
  </div>

  {{-- Summary Cards --}}
  @php
    $limit   = (float)$customer->credit_limit;
    $used    = (float)$customer->credit_balance;
    $avail   = max(0, $limit - $used);
  @endphp
  <div class="summary-row">
    <div class="summary-card card-primary">
      <div class="s-label">Credit Limit</div>
      <div class="s-value">৳ {{ number_format($limit, 2) }}</div>
    </div>
    <div class="summary-card card-red">
      <div class="s-label">Total Debit</div>
      <div class="s-value">৳ {{ number_format($totalDebit, 2) }}</div>
    </div>
    <div class="summary-card card-green">
      <div class="s-label">Total Credit</div>
      <div class="s-value">৳ {{ number_format($totalCredit, 2) }}</div>
    </div>
    <div class="summary-card {{ $closing > 0 ? 'card-red' : 'card-green' }}">
      <div class="s-label">Closing Balance</div>
      <div class="s-value">৳ {{ number_format($closing, 2) }}</div>
    </div>
  </div>

  {{-- Ledger Table --}}
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Date</th>
        <th>Description</th>
        <th>Ref</th>
        <th class="num">Debit (৳)</th>
        <th class="num">Credit (৳)</th>
        <th class="num">Balance (৳)</th>
      </tr>
    </thead>
    <tbody>
      @forelse($entries as $i => $entry)
      <tr>
        <td style="color:#94a3b8;font-size:10px;">{{ $i + 1 }}</td>
        <td style="white-space:nowrap;">{{ $entry->date?->format('d M Y') }}</td>
        <td>{{ $entry->description }}</td>
        <td style="font-size:10px;color:#6b7280;">
          {{ $entry->reference_type ? strtoupper($entry->reference_type) : '—' }}
          {{ $entry->reference_id ? '#' . $entry->reference_id : '' }}
        </td>
        <td class="num {{ (float)$entry->debit > 0 ? 'debit' : '' }}">
          {{ (float)$entry->debit > 0 ? number_format((float)$entry->debit, 2) : '—' }}
        </td>
        <td class="num {{ (float)$entry->credit > 0 ? 'credit' : '' }}">
          {{ (float)$entry->credit > 0 ? number_format((float)$entry->credit, 2) : '—' }}
        </td>
        <td class="num {{ (float)$entry->balance > 0 ? 'balance-pos' : 'balance-neg' }}">
          {{ number_format((float)$entry->balance, 2) }}
        </td>
      </tr>
      @empty
      <tr><td colspan="7" style="text-align:center;padding:24px;color:#94a3b8;font-style:italic;">No transactions found for this period.</td></tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4">TOTALS</td>
        <td class="num">{{ number_format($totalDebit, 2) }}</td>
        <td class="num">{{ number_format($totalCredit, 2) }}</td>
        <td class="num">{{ number_format($closing, 2) }}</td>
      </tr>
    </tfoot>
  </table>

  {{-- Footer --}}
  <div class="footer">
    <div class="note">
      <div>This is a computer-generated statement and does not require a physical signature.</div>
      <div style="margin-top:3px;">Generated by {{ $company->company_name }} · {{ now()->format('d M Y H:i:s') }}</div>
    </div>
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-label">Authorised Signatory</div>
    </div>
  </div>

</div>
</body>
</html>
