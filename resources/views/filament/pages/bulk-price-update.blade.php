<x-filament-panels::page>
<style>
/* ── Shared input base ──────────────────────────────────────────────────────── */
.bpu-control {
  width: 100%;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  transition: border-color .15s, box-shadow .15s;
  background: #ffffff;
  color: #0f172a;
  border: 1px solid #e2e8f0;
}
.dark .bpu-control {
  background: #1e293b;
  color: #f1f5f9;
  border-color: rgba(255,255,255,.1);
  color-scheme: dark;
}
.bpu-control:focus {
  outline: none;
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245,158,11,.18);
}

/* ── Select arrow ───────────────────────────────────────────────────────────── */
.bpu-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 16px;
  padding-right: 36px;
  cursor: pointer;
}

/* ── Date input dark fix ────────────────────────────────────────────────────── */
.dark input[type="date"] {
  color-scheme: dark;
}

/* ── Mechanism toggle ───────────────────────────────────────────────────────── */
.bpu-mech-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 11px 20px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all .15s;
  user-select: none;
  border: 1.5px solid #e2e8f0;
  background: #f8fafc;
  color: #64748b;
}
.dark .bpu-mech-btn {
  border-color: rgba(255,255,255,.1);
  background: rgba(255,255,255,.04);
  color: #94a3b8;
}
.bpu-mech-btn.active {
  border-color: #f59e0b;
  background: rgba(245,158,11,.08);
  color: #92400e;
}
.dark .bpu-mech-btn.active {
  background: rgba(245,158,11,.12);
  color: #fcd34d;
}
.bpu-mech-btn input[type=radio] { display: none; }

/* ── Price cell prefix ──────────────────────────────────────────────────────── */
.bpu-pfx {
  padding: 6px 9px;
  font-size: 12px;
  font-weight: 700;
  border-radius: 6px 0 0 6px;
  border: 1px solid #e2e8f0;
  border-right: none;
  background: #f1f5f9;
  color: #64748b;
}
.dark .bpu-pfx {
  background: #0f172a;
  border-color: rgba(255,255,255,.1);
  color: #64748b;
}
.bpu-cell-price {
  width: 108px;
  padding: 6px 8px;
  text-align: right;
  font-size: 13px;
  font-weight: 600;
  border-radius: 0 6px 6px 0;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #0f172a;
  transition: border-color .12s;
}
.dark .bpu-cell-price {
  background: #1e293b;
  border-color: rgba(255,255,255,.1);
  color: #f1f5f9;
  color-scheme: dark;
}
.bpu-cell-price:focus {
  outline: none;
  border-color: #f59e0b;
  box-shadow: 0 0 0 2px rgba(245,158,11,.18);
}

/* ── Table ──────────────────────────────────────────────────────────────────── */
.bpu-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.bpu-table thead tr { background: #1e293b; }
.dark .bpu-table thead tr { background: #0f172a; }
.bpu-table thead th {
  padding: 11px 16px;
  text-align: left;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #64748b;
  white-space: nowrap;
}
.bpu-table thead th.r { text-align: right; }
.bpu-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: background .1s;
}
.dark .bpu-table tbody tr { border-bottom-color: rgba(255,255,255,.06); }
.bpu-table tbody tr:nth-child(even) { background: #fafafa; }
.dark .bpu-table tbody tr:nth-child(even) { background: rgba(255,255,255,.02); }
.bpu-table tbody tr:hover { background: #f1f5f9; }
.dark .bpu-table tbody tr:hover { background: rgba(255,255,255,.05); }
.bpu-table tbody tr:last-child { border-bottom: none; }
.bpu-table tbody td { padding: 11px 16px; vertical-align: middle; }
.bpu-tr-base { background: rgba(245,158,11,.06) !important; }
.dark .bpu-tr-base { background: rgba(245,158,11,.08) !important; }

/* ── Badges ─────────────────────────────────────────────────────────────────── */
.bpu-badge {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}
.badge-a { background: rgba(37,99,235,.1);  color: #2563eb; }
.badge-b { background: rgba(5,150,105,.1);  color: #059669; }
.badge-c { background: rgba(217,119,6,.1);  color: #d97706; }
.badge-g { background: rgba(99,102,241,.1); color: #6366f1; }
.badge-x { background: rgba(100,116,139,.1); color: #64748b; }
.dark .badge-a { background: rgba(37,99,235,.18);  color: #93c5fd; }
.dark .badge-b { background: rgba(5,150,105,.18);  color: #6ee7b7; }
.dark .badge-c { background: rgba(217,119,6,.18);  color: #fcd34d; }
.dark .badge-g { background: rgba(99,102,241,.18); color: #a5b4fc; }
.dark .badge-x { background: rgba(100,116,139,.18); color: #94a3b8; }

/* ── Buttons ─────────────────────────────────────────────────────────────────── */
.bpu-btn-calc {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #f59e0b;
  color: #1c1400;
  border: none;
  padding: 9px 22px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: background .15s, transform .1s;
  white-space: nowrap;
}
.bpu-btn-calc:hover { background: #d97706; }
.bpu-btn-calc:active { transform: scale(.97); }
.bpu-btn-calc:disabled { opacity: .6; cursor: not-allowed; }

.bpu-btn-save {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #10b981;
  color: #fff;
  border: none;
  padding: 12px 32px;
  border-radius: 9px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: background .15s, transform .1s;
  box-shadow: 0 2px 10px rgba(16,185,129,.25);
}
.bpu-btn-save:hover { background: #059669; }
.bpu-btn-save:active { transform: scale(.97); }
.bpu-btn-save:disabled { opacity: .6; cursor: not-allowed; }
</style>

<div class="space-y-4">

  {{-- ═══════════════════════════════════════════════════════════════════════ --}}
  {{-- CARD: Product Selection                                                 --}}
  {{-- ═══════════════════════════════════════════════════════════════════════ --}}
  <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-800 p-6 shadow-sm">
    <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">Step 1</p>
    <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">Select Product to Update</h3>
    <div style="max-width:480px;">
      <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Product</label>
      <select class="bpu-control bpu-select" wire:model.live="productId">
        <option value="">— Choose a product —</option>
        @foreach($this->getProductOptions() as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
      </select>
    </div>
  </div>

  @if($productId && count($variantRows) > 0)

  {{-- ═══════════════════════════════════════════════════════════════════════ --}}
  {{-- CARD: Pricing Mechanism                                                 --}}
  {{-- ═══════════════════════════════════════════════════════════════════════ --}}
  <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-800 p-6 shadow-sm">

    <div class="mb-5 flex items-baseline gap-3">
      <div>
        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">Step 2</p>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Pricing Mechanism</h3>
      </div>
      <span class="ml-auto rounded-full bg-amber-100 dark:bg-amber-900/30 px-3 py-1 text-xs font-bold text-amber-700 dark:text-amber-300">
        {{ $productName }}
      </span>
    </div>

    {{-- Mechanism toggle --}}
    <div class="flex flex-wrap gap-3">
      <label class="bpu-mech-btn {{ $mechanism === 'manual' ? 'active' : '' }}">
        <input type="radio" wire:model.live="mechanism" value="manual">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
        Manual
        <span class="text-[11px] font-normal opacity-70">— set each price independently</span>
      </label>
      <label class="bpu-mech-btn {{ $mechanism === 'formula' ? 'active' : '' }}">
        <input type="radio" wire:model.live="mechanism" value="formula">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        Formula
        <span class="text-[11px] font-normal opacity-70">— derive from base price</span>
      </label>
    </div>

    {{-- ── Formula controls ── --}}
    @if($mechanism === 'formula')
    <div class="mt-5 space-y-5">

      {{-- Formula explanation banner --}}
      <div class="flex items-start gap-3 rounded-lg border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-900/20 px-4 py-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <p class="text-xs leading-relaxed text-amber-800 dark:text-amber-300">
          <strong>Formula:</strong>
          price = round( (base ÷ <strong>{{ $baseWeight }}</strong>) × weight, nearest <strong>{{ $weightRounding }}</strong> )
          + <strong>{{ $weightPremium }}</strong> BDT
          &nbsp;·&nbsp;
          Non-base branches: + <strong>{{ $branchPremium }}</strong> BDT
        </p>
      </div>

      {{-- Base branch + weight --}}
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">
            Base Branch <span class="font-normal text-gray-400">(reference / lowest price)</span>
          </label>
          <select class="bpu-control bpu-select" wire:model.live="baseBranchId">
            <option value="">— select base branch —</option>
            @foreach($branchOptions as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Base Weight (kg)</label>
          <input type="number" class="bpu-control" wire:model.live="baseWeight" min="1" step="0.01">
        </div>
      </div>

      {{-- Premiums row --}}
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Branch Premium (৳)</label>
          <input type="number" class="bpu-control" wire:model.live="branchPremium" step="0.01" placeholder="e.g. 10">
          <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">Added per non-base branch</p>
        </div>
        <div>
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Weight Rounding</label>
          <input type="number" class="bpu-control" wire:model.live="weightRounding" min="1" step="1" placeholder="e.g. 5">
          <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">Round to nearest N BDT</p>
        </div>
        <div>
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Weight Premium (৳)</label>
          <input type="number" class="bpu-control" wire:model.live="weightPremium" step="0.01" placeholder="e.g. 25">
          <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">Added after proportional calc</p>
        </div>
      </div>

      {{-- Base price + recalc --}}
      <div class="flex flex-wrap items-end gap-4 border-t border-dashed border-gray-200 dark:border-white/10 pt-5">
        <div>
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">
            Base Price (৳) &mdash; {{ $baseWeight }}kg @ {{ $branchOptions[$baseBranchId] ?? 'base branch' }}
          </label>
          <div class="flex items-center">
            <span class="bpu-pfx text-sm font-bold">৳</span>
            <input
              type="number"
              wire:model.live="basePrice"
              step="0.01" min="0"
              placeholder="e.g. 2040"
              style="width:160px;padding:8px 12px;font-size:15px;font-weight:700;border-radius:0 8px 8px 0;border:1px solid #e2e8f0;background:#fff;color:#0f172a;"
              class="bpu-cell-price dark:[background:#1e293b] dark:[color:#f1f5f9] focus:outline-none focus:border-amber-400"
            >
          </div>
        </div>
        <button class="bpu-btn-calc" wire:click="recalculate" type="button"
                wire:loading.attr="disabled" wire:target="recalculate">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          <span wire:loading.remove wire:target="recalculate">Recalculate All</span>
          <span wire:loading wire:target="recalculate">Calculating…</span>
        </button>
      </div>

    </div>
    @endif
  </div>

  {{-- ═══════════════════════════════════════════════════════════════════════ --}}
  {{-- CARD: Variant Prices Table  (Alpine-driven — no wire:model on inputs)  --}}
  {{-- ═══════════════════════════════════════════════════════════════════════ --}}
  <div
    x-data="{
      prices: @js($variantPrices),
      collectAndSave() {
        const rows = this.$el.querySelectorAll('[data-vid]');
        const collected = {};
        rows.forEach(row => {
          const vid = row.dataset.vid;
          const priceInput = row.querySelector('[data-price]');
          const dateInput  = row.querySelector('[data-date]');
          collected[vid] = {
            price:          priceInput ? priceInput.value : '',
            effective_date: dateInput  ? dateInput.value  : '',
          };
        });
        $wire.saveWithPrices(collected);
      }
    }"
    wire:key="price-table-{{ $productId }}"
    class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-800 shadow-sm"
  >

    {{-- Table header --}}
    <div class="flex items-center justify-between border-b border-gray-100 dark:border-white/10 px-6 py-4">
      <div>
        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">Step 3</p>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
          Variant Prices
          <span class="ml-2 rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-semibold text-gray-500 dark:text-gray-400">
            {{ count($variantRows) }} {{ Str::plural('variant', count($variantRows)) }}
          </span>
        </h3>
      </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
      <table class="bpu-table">
        <thead>
          <tr>
            <th>Variant / Display Name</th>
            <th style="text-align:center;">Weight</th>
            <th style="text-align:center;">Grade</th>
            <th>Branch / Factory</th>
            <th class="r">Price (৳)</th>
            <th>Effective Date</th>
          </tr>
        </thead>
        <tbody>
          @foreach($variantRows as $variantId => $row)
            @php
              $bl = strtolower($row['branch'] ?? '');
              $bc = str_contains($bl, 'demra')      ? 'badge-a'
                 : (str_contains($bl, 'sirajgonj') ? 'badge-b'
                 : (str_contains($bl, 'rampura')   ? 'badge-c'
                 : 'badge-x'));
              $isBase = $mechanism === 'formula'
                && (float)$row['weight'] === (float)$baseWeight
                && $row['branch_id'] == $baseBranchId;
            @endphp
            <tr data-vid="{{ $variantId }}" class="{{ $isBase ? 'bpu-tr-base' : '' }}">

              {{-- Name --}}
              <td>
                <p class="text-xs leading-snug text-gray-700 dark:text-gray-300 max-w-[260px]">
                  {{ $row['label'] }}
                </p>
                @if($isBase)
                  <span class="mt-0.5 inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-amber-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    Base variant
                  </span>
                @endif
              </td>

              {{-- Weight --}}
              <td style="text-align:center;">
                <span class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ $row['weight'] ? (int)$row['weight'] : '—' }}</span>
                <span class="text-[10px] text-gray-400 dark:text-gray-500"> kg</span>
              </td>

              {{-- Grade --}}
              <td style="text-align:center;">
                @if($row['grade'])
                  <span class="bpu-badge badge-g">{{ strtoupper($row['grade']) }}</span>
                @else
                  <span class="text-gray-300 dark:text-gray-600">—</span>
                @endif
              </td>

              {{-- Branch --}}
              <td>
                <span class="bpu-badge {{ $bc }}">{{ $row['branch'] }}</span>
              </td>

              {{-- Price — plain HTML input, value set by PHP, collected by Alpine on save --}}
              <td style="text-align:right;">
                <div class="inline-flex items-center justify-end">
                  <span class="bpu-pfx">৳</span>
                  <input
                    type="number"
                    class="bpu-cell-price"
                    data-price
                    value="{{ $variantPrices[$variantId]['price'] ?? '' }}"
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
                  class="bpu-control"
                  data-date
                  value="{{ $variantPrices[$variantId]['effective_date'] ?? now()->toDateString() }}"
                  style="width:140px;padding:5px 10px;font-size:12px;"
                >
              </td>

            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- ── Save footer (inside Alpine x-data scope) ─────────────────────── --}}
    <div class="flex justify-end px-6 py-5 border-t border-gray-100 dark:border-white/10">
      <button class="bpu-btn-save" type="button"
              x-on:click="collectAndSave()"
              wire:loading.attr="disabled" wire:target="saveWithPrices">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span wire:loading.remove wire:target="saveWithPrices">Save All Prices</span>
        <span wire:loading wire:target="saveWithPrices">Saving…</span>
      </button>
    </div>

  </div>

  {{-- ── No variants found ──────────────────────────────────────────────── --}}
  @elseif($productId)
  <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-800 px-6 py-16 text-center shadow-sm">
    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
    </div>
    <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">No active variants found</p>
    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Add variants first from the <strong>Products</strong> section.</p>
  </div>

  {{-- ── Nothing selected ───────────────────────────────────────────────── --}}
  @else
  <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-800 px-6 py-16 text-center shadow-sm">
    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">Select a product to get started</p>
    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Choose a product above to manage its variant prices.</p>
  </div>
  @endif

</div>
</x-filament-panels::page>
