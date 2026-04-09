<x-filament-panels::page>
<style>
/* ── Variables ─────────────────────────────────────────────────────────────── */
:root {
  --bpu-bg:        #ffffff;
  --bpu-bg-alt:    #f8fafc;
  --bpu-border:    #e2e8f0;
  --bpu-text:      #0f172a;
  --bpu-muted:     #64748b;
  --bpu-label:     #374151;
  --bpu-input-bg:  #ffffff;
  --bpu-thead:     #1e293b;
  --bpu-row-alt:   #f8fafc;
  --bpu-row-hover: #f1f5f9;
}
.dark {
  --bpu-bg:        #1e293b;
  --bpu-bg-alt:    #0f172a;
  --bpu-border:    rgba(255,255,255,.1);
  --bpu-text:      #f1f5f9;
  --bpu-muted:     #94a3b8;
  --bpu-label:     #cbd5e1;
  --bpu-input-bg:  #0f172a;
  --bpu-thead:     #0f172a;
  --bpu-row-alt:   rgba(255,255,255,.03);
  --bpu-row-hover: rgba(255,255,255,.06);
}

/* ── Layout ────────────────────────────────────────────────────────────────── */
.bpu-card {
  background: var(--bpu-bg);
  border: 1px solid var(--bpu-border);
  border-radius: 12px;
  padding: 22px 26px;
  margin-bottom: 16px;
}
.bpu-section-title {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .1em;
  color: var(--bpu-muted);
  margin-bottom: 14px;
}
.bpu-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: var(--bpu-label);
  margin-bottom: 5px;
}
.bpu-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.bpu-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }

/* ── Inputs ────────────────────────────────────────────────────────────────── */
.bpu-input, .bpu-select {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--bpu-border);
  border-radius: 7px;
  font-size: 13px;
  color: var(--bpu-text);
  background: var(--bpu-input-bg);
  transition: border-color .15s, box-shadow .15s;
}
.bpu-input:focus, .bpu-select:focus {
  outline: none;
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245,158,11,.18);
}
.bpu-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 16px;
  padding-right: 36px;
}

/* ── Mechanism toggle ──────────────────────────────────────────────────────── */
.bpu-mechanism-group { display: flex; gap: 10px; flex-wrap: wrap; }
.bpu-mechanism-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border: 1.5px solid var(--bpu-border);
  border-radius: 9px;
  font-size: 13px;
  font-weight: 600;
  color: var(--bpu-muted);
  cursor: pointer;
  background: var(--bpu-bg-alt);
  transition: all .15s;
  user-select: none;
}
.bpu-mechanism-btn.active {
  border-color: #f59e0b;
  background: rgba(245,158,11,.1);
  color: #b45309;
}
.dark .bpu-mechanism-btn.active { color: #fde68a; }
.bpu-mechanism-btn input[type=radio] { display: none; }

/* ── Formula banner ────────────────────────────────────────────────────────── */
.bpu-formula-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(245,158,11,.08);
  border: 1px solid rgba(245,158,11,.3);
  border-radius: 8px;
  padding: 11px 16px;
  font-size: 12px;
  color: #92400e;
  margin-bottom: 18px;
}
.dark .bpu-formula-banner { color: #fde68a; background: rgba(245,158,11,.12); }
.bpu-formula-icon { font-size: 18px; flex-shrink: 0; }

/* ── Base price row ────────────────────────────────────────────────────────── */
.bpu-base-row {
  display: flex;
  align-items: flex-end;
  gap: 14px;
  flex-wrap: wrap;
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px dashed var(--bpu-border);
}
.bpu-base-price-wrap { display: flex; align-items: center; gap: 0; }
.bpu-prefix {
  background: var(--bpu-bg-alt);
  border: 1px solid var(--bpu-border);
  border-right: none;
  padding: 8px 10px;
  font-size: 13px;
  font-weight: 700;
  color: var(--bpu-muted);
  border-radius: 7px 0 0 7px;
}
.bpu-base-price-input {
  width: 140px;
  padding: 8px 12px;
  border: 1px solid var(--bpu-border);
  border-radius: 0 7px 7px 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--bpu-text);
  background: var(--bpu-input-bg);
}
.bpu-base-price-input:focus {
  outline: none;
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245,158,11,.18);
}

/* ── Buttons ───────────────────────────────────────────────────────────────── */
.bpu-btn-recalc {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #f59e0b;
  color: #1c1400;
  border: none;
  padding: 9px 20px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: background .15s, transform .1s;
}
.bpu-btn-recalc:hover { background: #d97706; }
.bpu-btn-recalc:active { transform: scale(.97); }

.bpu-btn-save {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #10b981;
  color: #fff;
  border: none;
  padding: 11px 28px;
  border-radius: 9px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: background .15s, transform .1s;
  box-shadow: 0 2px 8px rgba(16,185,129,.25);
}
.bpu-btn-save:hover { background: #059669; }
.bpu-btn-save:active { transform: scale(.97); }
.bpu-btn-save:disabled { opacity: .6; cursor: not-allowed; }

/* ── Variants table ────────────────────────────────────────────────────────── */
.bpu-table-wrap {
  overflow-x: auto;
  border-radius: 9px;
  border: 1px solid var(--bpu-border);
}
.bpu-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.bpu-table thead tr {
  background: var(--bpu-thead);
}
.bpu-table thead th {
  padding: 10px 14px;
  text-align: left;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #94a3b8;
  white-space: nowrap;
}
.bpu-table thead th.r { text-align: right; }
.bpu-table tbody tr {
  border-bottom: 1px solid var(--bpu-border);
  transition: background .1s;
}
.bpu-table tbody tr:nth-child(even) { background: var(--bpu-row-alt); }
.bpu-table tbody tr:hover { background: var(--bpu-row-hover); }
.bpu-table tbody tr:last-child { border-bottom: none; }
.bpu-table tbody td {
  padding: 10px 14px;
  vertical-align: middle;
  color: var(--bpu-text);
}

/* ── Cell inputs ───────────────────────────────────────────────────────────── */
.bpu-price-wrap {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0;
}
.bpu-price-pfx {
  background: var(--bpu-bg-alt);
  border: 1px solid var(--bpu-border);
  border-right: none;
  padding: 5px 7px;
  font-size: 11px;
  font-weight: 700;
  color: var(--bpu-muted);
  border-radius: 5px 0 0 5px;
}
.bpu-cell-price {
  width: 100px;
  padding: 5px 8px;
  border: 1px solid var(--bpu-border);
  border-radius: 0 5px 5px 0;
  font-size: 13px;
  font-weight: 600;
  text-align: right;
  background: var(--bpu-input-bg);
  color: var(--bpu-text);
  transition: border-color .12s;
}
.bpu-cell-price:focus {
  outline: none;
  border-color: #f59e0b;
}
.bpu-cell-date {
  width: 128px;
  padding: 5px 8px;
  border: 1px solid var(--bpu-border);
  border-radius: 5px;
  font-size: 12px;
  background: var(--bpu-input-bg);
  color: var(--bpu-text);
}
.bpu-cell-date:focus { outline: none; border-color: #f59e0b; }

/* ── Badges ────────────────────────────────────────────────────────────────── */
.bpu-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}
.badge-demra     { background: rgba(37,99,235,.12);  color: #2563eb; }
.badge-sirajgonj { background: rgba(5,150,105,.12);  color: #059669; }
.badge-rampura   { background: rgba(217,119,6,.12);  color: #d97706; }
.badge-grade     { background: rgba(99,102,241,.12); color: #6366f1; }
.badge-other     { background: rgba(100,116,139,.1); color: #64748b; }
.dark .badge-demra     { background: rgba(37,99,235,.2);  color: #93c5fd; }
.dark .badge-sirajgonj { background: rgba(5,150,105,.2);  color: #6ee7b7; }
.dark .badge-rampura   { background: rgba(217,119,6,.2);  color: #fcd34d; }
.dark .badge-grade     { background: rgba(99,102,241,.2); color: #a5b4fc; }
.dark .badge-other     { background: rgba(100,116,139,.2); color: #94a3b8; }

/* ── Empty / no-product state ──────────────────────────────────────────────── */
.bpu-empty {
  text-align: center;
  padding: 48px 24px;
  color: var(--bpu-muted);
  font-size: 14px;
}
.bpu-empty-icon { font-size: 40px; margin-bottom: 12px; opacity: .5; }

/* ── Responsive ────────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
  .bpu-grid-2, .bpu-grid-3 { grid-template-columns: 1fr; }
  .bpu-base-row { flex-direction: column; align-items: stretch; }
}
</style>

<div>

  {{-- ── Product select ──────────────────────────────────────────────────── --}}
  <div class="bpu-card">
    <div class="bpu-section-title">Select Product to Update</div>
    <div style="max-width:460px;">
      <label class="bpu-label">Product</label>
      <select class="bpu-select" wire:model.live="productId">
        <option value="">— Choose a product —</option>
        @foreach($this->getProductOptions() as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
      </select>
    </div>
  </div>

  @if($productId && count($variantRows) > 0)

  {{-- ── Pricing mechanism ───────────────────────────────────────────────── --}}
  <div class="bpu-card">
    <div class="bpu-section-title">Pricing Mechanism — <span style="color:var(--bpu-text);font-weight:700;">{{ $productName }}</span></div>

    <div class="bpu-mechanism-group">
      <label class="bpu-mechanism-btn {{ $mechanism === 'manual' ? 'active' : '' }}">
        <input type="radio" wire:model.live="mechanism" value="manual">
        ✋ Manual
        <span style="font-weight:400;font-size:11px;margin-left:2px;">— set each price independently</span>
      </label>
      <label class="bpu-mechanism-btn {{ $mechanism === 'formula' ? 'active' : '' }}">
        <input type="radio" wire:model.live="mechanism" value="formula">
        ⚡ Formula
        <span style="font-weight:400;font-size:11px;margin-left:2px;">— derive from base price</span>
      </label>
    </div>

    @if($mechanism === 'formula')
    <div style="margin-top:20px;">
      <div class="bpu-formula-banner">
        <span class="bpu-formula-icon">📐</span>
        <div>
          <strong>Weight formula:</strong>
          price = round( (base ÷ {{ $baseWeight }}) × weight, nearest <strong>{{ $weightRounding }}</strong> ) + <strong>{{ $weightPremium }}</strong> BDT
          &nbsp;·&nbsp;
          <strong>Branch premium:</strong> + <strong>{{ $branchPremium }}</strong> BDT per non-base branch
        </div>
      </div>

      <div class="bpu-grid-2" style="margin-bottom:14px;">
        <div>
          <label class="bpu-label">Base Branch <span style="font-weight:400;color:var(--bpu-muted);">(reference / lowest price)</span></label>
          <select class="bpu-select" wire:model.live="baseBranchId">
            <option value="">— select base branch —</option>
            @foreach($branchOptions as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="bpu-label">Base Weight (kg)</label>
          <input type="number" class="bpu-input" wire:model.live="baseWeight" min="1" step="0.01">
        </div>
      </div>

      <div class="bpu-grid-3" style="margin-bottom:4px;">
        <div>
          <label class="bpu-label">Branch Premium (৳)</label>
          <input type="number" class="bpu-input" wire:model.live="branchPremium" step="0.01"
                 placeholder="e.g. 10">
          <div style="font-size:11px;color:var(--bpu-muted);margin-top:4px;">Added per non-base branch</div>
        </div>
        <div>
          <label class="bpu-label">Weight Rounding</label>
          <input type="number" class="bpu-input" wire:model.live="weightRounding" min="1" step="1"
                 placeholder="e.g. 5">
          <div style="font-size:11px;color:var(--bpu-muted);margin-top:4px;">Round to nearest N BDT</div>
        </div>
        <div>
          <label class="bpu-label">Weight Premium (৳)</label>
          <input type="number" class="bpu-input" wire:model.live="weightPremium" step="0.01"
                 placeholder="e.g. 25">
          <div style="font-size:11px;color:var(--bpu-muted);margin-top:4px;">Added after proportional calc</div>
        </div>
      </div>

      <div class="bpu-base-row">
        <div>
          <label class="bpu-label">
            Base Price (৳) &mdash;
            {{ $baseWeight }}kg &nbsp;@&nbsp; {{ $branchOptions[$baseBranchId] ?? 'base branch' }}
          </label>
          <div class="bpu-base-price-wrap">
            <span class="bpu-prefix">৳</span>
            <input type="number" class="bpu-base-price-input"
                   wire:model.live="basePrice" step="0.01" min="0" placeholder="e.g. 2040">
          </div>
        </div>
        <button class="bpu-btn-recalc" wire:click="recalculate" type="button"
                wire:loading.attr="disabled" wire:target="recalculate">
          <span wire:loading.remove wire:target="recalculate">⚡ Recalculate All</span>
          <span wire:loading wire:target="recalculate">Calculating…</span>
        </button>
      </div>
    </div>
    @endif
  </div>

  {{-- ── Variant prices table ─────────────────────────────────────────────── --}}
  <div class="bpu-card" style="padding:22px 0;">
    <div class="bpu-section-title" style="padding:0 26px 10px;">
      Variant Prices
      <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:12px;color:var(--bpu-muted);">
        — {{ count($variantRows) }} variant{{ count($variantRows) !== 1 ? 's' : '' }}
      </span>
    </div>

    <div class="bpu-table-wrap" style="border-radius:0;border-left:none;border-right:none;">
      <table class="bpu-table">
        <thead>
          <tr>
            <th>Product / Display Name</th>
            <th style="text-align:center;">Weight</th>
            <th style="text-align:center;">Grade</th>
            <th>Factory / Branch</th>
            <th class="r">Active Price (৳)</th>
            <th>Effective Date</th>
          </tr>
        </thead>
        <tbody>
          @foreach($variantRows as $variantId => $row)
            @php
              $bl = strtolower($row['branch'] ?? '');
              $bc = str_contains($bl,'demra')     ? 'badge-demra'
                 : (str_contains($bl,'sirajgonj') ? 'badge-sirajgonj'
                 : (str_contains($bl,'rampura')   ? 'badge-rampura'
                 : 'badge-other'));
              $isBase = $mechanism === 'formula'
                && (float)$row['weight'] === (float)$baseWeight
                && $row['branch_id'] == $baseBranchId;
            @endphp
            <tr style="{{ $isBase ? 'background:rgba(245,158,11,.07);' : '' }}">

              {{-- Name --}}
              <td>
                <div style="font-size:12px;color:var(--bpu-muted);max-width:260px;line-height:1.4;">
                  {{ $row['label'] }}
                </div>
                @if($isBase)
                  <span style="font-size:10px;font-weight:700;color:#f59e0b;letter-spacing:.05em;">⭐ BASE</span>
                @endif
              </td>

              {{-- Weight --}}
              <td style="text-align:center;">
                <span style="font-weight:700;font-size:14px;color:var(--bpu-text);">{{ $row['weight'] ? (int)$row['weight'] : '—' }}</span>
                <span style="font-size:10px;color:var(--bpu-muted);margin-left:1px;">kg</span>
              </td>

              {{-- Grade --}}
              <td style="text-align:center;">
                @if($row['grade'])
                  <span class="bpu-badge badge-grade">{{ strtoupper($row['grade']) }}</span>
                @else
                  <span style="color:var(--bpu-muted);">—</span>
                @endif
              </td>

              {{-- Branch --}}
              <td>
                <span class="bpu-badge {{ $bc }}">{{ $row['branch'] }}</span>
              </td>

              {{-- Price --}}
              <td style="text-align:right;">
                <div class="bpu-price-wrap">
                  <span class="bpu-price-pfx">৳</span>
                  <input
                    type="number"
                    class="bpu-cell-price"
                    wire:model.defer="variantPrices.{{ $variantId }}.price"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                  >
                </div>
              </td>

              {{-- Effective date --}}
              <td>
                <input
                  type="date"
                  class="bpu-cell-date"
                  wire:model.defer="variantPrices.{{ $variantId }}.effective_date"
                >
              </td>

            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- ── Save footer ──────────────────────────────────────────────────────── --}}
  <div style="display:flex;justify-content:flex-end;padding-bottom:32px;">
    <button class="bpu-btn-save" wire:click="save" type="button"
            wire:loading.attr="disabled" wire:target="save">
      <span wire:loading.remove wire:target="save">✓&nbsp; Save All Prices</span>
      <span wire:loading wire:target="save">Saving…</span>
    </button>
  </div>

  {{-- No variants found --}}
  @elseif($productId)
  <div class="bpu-card">
    <div class="bpu-empty">
      <div class="bpu-empty-icon">📦</div>
      No active variants found for this product.<br>
      <span style="font-size:12px;">Add variants first from the <strong>Products</strong> section.</span>
    </div>
  </div>

  {{-- Nothing selected yet --}}
  @else
  <div class="bpu-card">
    <div class="bpu-empty">
      <div class="bpu-empty-icon">💰</div>
      Select a product above to manage its variant prices.
    </div>
  </div>
  @endif

</div>
</x-filament-panels::page>
