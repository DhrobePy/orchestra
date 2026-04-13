<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Expense Voucher — {{ $voucher->voucher_number }}</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #111827; background: #fff; }
  .page { max-width: 780px; margin: 0 auto; padding: 32px 36px; }

  /* Print bar */
  .print-bar { background: #7c3aed; color: #fff; padding: 10px 36px; display: flex; align-items: center; justify-content: space-between; }
  .print-btn { background: #a78bfa; color: #fff; border: none; padding: 7px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
  .close-btn { background: transparent; color: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.3); padding: 7px 14px; border-radius: 6px; font-size: 13px; cursor: pointer; }

  /* Header */
  .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 18px; border-bottom: 3px solid #7c3aed; margin-bottom: 22px; }
  .brand-name { font-size: 22px; font-weight: 900; color: #7c3aed; }
  .brand-sub  { font-size: 11px; color: #94a3b8; margin-top: 2px; }
  .brand-addr { font-size: 11px; color: #64748b; margin-top: 4px; line-height: 1.5; }
  .voucher-meta { text-align: right; }
  .voucher-title { font-size: 20px; font-weight: 800; color: #7c3aed; }
  .voucher-num   { font-size: 14px; color: #374151; font-weight: 700; margin-top: 4px; }
  .voucher-date  { font-size: 11px; color: #6b7280; margin-top: 2px; }

  /* Amount hero */
  .amount-hero { background: linear-gradient(135deg, #faf5ff, #ede9fe); border: 2px solid #c4b5fd; border-radius: 14px; padding: 22px 28px; text-align: center; margin-bottom: 22px; }
  .amount-hero .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #7c3aed; margin-bottom: 6px; }
  .amount-hero .value { font-size: 42px; font-weight: 900; color: #6d28d9; line-height: 1; }
  .amount-hero .currency { font-size: 24px; vertical-align: super; margin-right: 4px; }

  /* Details grid */
  .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 22px; }
  .detail-card { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; }
  .detail-card.full { grid-column: 1 / -1; }
  .dc-label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 5px; }
  .dc-value { font-size: 13px; font-weight: 600; color: #1e293b; }
  .dc-sub   { font-size: 11px; color: #64748b; margin-top: 2px; }

  /* Payment section */
  .payment-section { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px 18px; margin-bottom: 22px; }
  .payment-section.bank { background: #eff6ff; border-color: #bfdbfe; }
  .payment-header { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; margin-bottom: 10px; }
  .payment-method-badge { display: inline-block; background: #7c3aed; color: #fff; padding: 3px 12px; border-radius: 999px; font-size: 11px; font-weight: 600; margin-bottom: 10px; }
  .payment-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
  .payment-field .pf-label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 3px; }
  .payment-field .pf-value { font-size: 12px; font-weight: 600; color: #1e293b; }

  /* Status badge */
  .status-badge { display: inline-block; padding: 4px 14px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
  .status-pending  { background: #fef3c7; color: #92400e; }
  .status-approved { background: #dcfce7; color: #166534; }
  .status-rejected { background: #fee2e2; color: #991b1b; }
  .status-paid     { background: #dbeafe; color: #1e40af; }

  /* Signature row */
  .sig-row { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 36px; padding-top: 16px; border-top: 1px solid #e5e7eb; }
  .sig-block { text-align: center; width: 180px; }
  .sig-line  { border-top: 1px solid #374151; margin-bottom: 6px; }
  .sig-label { font-size: 10px; color: #6b7280; }

  /* Footer */
  .doc-footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; }
  .footer-note { font-size: 10px; color: #94a3b8; line-height: 1.6; }
  .watermark   { font-size: 10px; color: #94a3b8; text-align: right; }

  /* Section divider */
  .section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; margin-bottom: 8px; }

  @media print {
    .print-bar { display: none; }
    .page { padding: 16px 20px; }
    @page { margin: 10mm; size: A4; }
  }
</style>
</head>
<body>

<div class="print-bar">
  <span style="font-weight:700;font-size:14px;">Expense Voucher — {{ $voucher->voucher_number }}</span>
  <div style="display:flex;gap:10px;">
    <button class="print-btn" onclick="window.print()">Print</button>
    <button class="close-btn" onclick="window.close()">Close</button>
  </div>
</div>

<div class="page">

  {{-- ── Header ─────────────────────────────────────────────────────────── --}}
  <div class="header">
    <div>
      <div class="brand-name">{{ $company->company_name }}</div>
      @if($company->tagline)
        <div class="brand-sub">{{ $company->tagline }}</div>
      @endif
      @if($company->address)
        <div class="brand-addr">{{ $company->address }}</div>
      @endif
      @if($company->phone)
        <div class="brand-addr">Tel: {{ $company->phone }}</div>
      @endif
    </div>
    <div class="voucher-meta">
      <div class="voucher-title">EXPENSE VOUCHER</div>
      <div class="voucher-num">{{ $voucher->voucher_number }}</div>
      <div class="voucher-date">
        {{ \Carbon\Carbon::parse($voucher->voucher_date)->format('d M Y') }}
      </div>
      @php
        $statusClass = match($voucher->status ?? '') {
            'approved' => 'status-approved',
            'rejected' => 'status-rejected',
            'paid'     => 'status-paid',
            default    => 'status-pending',
        };
      @endphp
      <div style="margin-top:8px;">
        <span class="status-badge {{ $statusClass }}">
          {{ ucfirst($voucher->status ?? 'Pending') }}
        </span>
      </div>
    </div>
  </div>

  {{-- ── Amount Hero ─────────────────────────────────────────────────────── --}}
  <div class="amount-hero">
    <div class="label">Total Expense Amount</div>
    <div class="value">
      <span class="currency">৳</span>{{ number_format((float) $voucher->amount, 2) }}
    </div>
  </div>

  {{-- ── Details Grid ─────────────────────────────────────────────────────── --}}
  <div class="details-grid">

    <div class="detail-card">
      <div class="dc-label">Category</div>
      <div class="dc-value">{{ $category?->name ?? '—' }}</div>
    </div>

    <div class="detail-card">
      <div class="dc-label">Subcategory</div>
      <div class="dc-value">{{ $subcategory?->name ?? '—' }}</div>
    </div>

    <div class="detail-card">
      <div class="dc-label">Branch</div>
      <div class="dc-value">{{ $branch?->name ?? '—' }}</div>
      @if($branch?->code)
        <div class="dc-sub">Code: {{ $branch->code }}</div>
      @endif
    </div>

    <div class="detail-card">
      <div class="dc-label">Voucher Date</div>
      <div class="dc-value">{{ \Carbon\Carbon::parse($voucher->voucher_date)->format('d M Y') }}</div>
    </div>

    @if($voucher->reference)
    <div class="detail-card">
      <div class="dc-label">Reference</div>
      <div class="dc-value">{{ $voucher->reference }}</div>
    </div>
    @endif

    @if($approvedBy)
    <div class="detail-card">
      <div class="dc-label">Approved By</div>
      <div class="dc-value">{{ $approvedBy->name ?? $approvedBy->first_name . ' ' . ($approvedBy->last_name ?? '') }}</div>
    </div>
    @endif

    @if($voucher->description)
    <div class="detail-card full">
      <div class="dc-label">Description / Narration</div>
      <div class="dc-value" style="font-weight:400;line-height:1.6;">{{ $voucher->description }}</div>
    </div>
    @endif

  </div>

  {{-- ── Payment Method ───────────────────────────────────────────────────── --}}
  @if($voucher->payment_method)
  @php
    $isBankPayment = in_array($voucher->payment_method, ['bank_transfer', 'cheque']);
    $methodLabel = match($voucher->payment_method) {
        'cash'           => 'Cash',
        'bank_transfer'  => 'Bank Transfer',
        'cheque'         => 'Cheque',
        'mobile'         => 'Mobile Banking',
        default          => ucfirst(str_replace('_', ' ', $voucher->payment_method)),
    };
  @endphp
  <div class="section-label">Payment Details</div>
  <div class="payment-section {{ $isBankPayment ? 'bank' : '' }}">
    <span class="payment-method-badge">{{ $methodLabel }}</span>

    @if($isBankPayment && $bankAccount)
    <div class="payment-grid">
      <div class="payment-field">
        <div class="pf-label">Bank</div>
        <div class="pf-value">{{ $bankAccount->bank_name }}</div>
      </div>
      <div class="payment-field">
        <div class="pf-label">Account Name</div>
        <div class="pf-value">{{ $bankAccount->account_name }}</div>
      </div>
      <div class="payment-field">
        <div class="pf-label">Account Number</div>
        <div class="pf-value">{{ $bankAccount->account_number }}</div>
      </div>
    </div>
    @elseif($voucher->payment_method === 'cash')
    <div style="font-size:12px;color:#166534;font-weight:600;">Paid in Cash</div>
    @endif
  </div>
  @endif

  {{-- ── Signature Row ─────────────────────────────────────────────────────── --}}
  <div class="sig-row">
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-label">Prepared By</div>
    </div>
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-label">Verified By</div>
    </div>
    <div class="sig-block">
      @if($approvedBy)
        <div class="sig-line"></div>
        <div class="sig-label">
          Approved By<br>
          <strong>{{ $approvedBy->name ?? ($approvedBy->first_name . ' ' . ($approvedBy->last_name ?? '')) }}</strong>
        </div>
      @else
        <div class="sig-line"></div>
        <div class="sig-label">Approved By</div>
      @endif
    </div>
  </div>

  {{-- ── Footer ───────────────────────────────────────────────────────────── --}}
  <div class="doc-footer">
    <div class="footer-note">
      This is a computer-generated expense voucher.<br>
      {{ $company->company_name }} &mdash; {{ $company->address }}
    </div>
    <div class="watermark">
      Printed: {{ now()->format('d M Y H:i') }}<br>
      {{ $voucher->voucher_number }}
    </div>
  </div>

</div>

</body>
</html>
