<?php

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\GrnWeightVariance;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProcurementService
{
    // ── Number Generation ─────────────────────────────────────────────────────

    public function generatePoNumber(): string
    {
        $prefix = config('procurement.defaults.po_number_prefix', 'PO');
        $last = PurchaseOrder::withTrashed()
            ->where('po_number', 'like', $prefix . '-%')
            ->orderByDesc('id')
            ->value('po_number');

        $next = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;

        return $prefix . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function generateGrnNumber(): string
    {
        $prefix = config('procurement.defaults.grn_number_prefix', 'GRN');
        $date = now()->format('Ymd');
        $todayCount = GoodsReceivedNote::whereDate('created_at', today())->count();

        return $prefix . '-' . $date . '-' . str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);
    }

    public function generateVoucherNumber(): string
    {
        $prefix = config('procurement.defaults.payment_voucher_prefix', 'PV');
        $date = now()->format('Ymd');
        $todayCount = PurchasePayment::whereDate('created_at', today())->count();

        return $prefix . '-' . $date . '-' . str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);
    }

    // ── Create Purchase Order ─────────────────────────────────────────────────

    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $supplier = Supplier::findOrFail($data['supplier_id']);

            $data['supplier_name'] = $supplier->company_name;
            // Use manually entered PO number if provided, otherwise auto-generate
            $data['po_number']     = !empty(trim($data['po_number'] ?? ''))
                ? trim($data['po_number'])
                : $this->generatePoNumber();
            $data['created_by']    = Auth::id();

            // Calculate total
            if (!empty($data['quantity']) && !empty($data['unit_price'])) {
                $data['total_order_value'] = round(
                    (float) $data['quantity'] * (float) $data['unit_price'],
                    2
                );
            }

            // Auto-approve if po_approval feature is disabled
            if (!config('procurement.features.po_approval', false)) {
                $data['po_status']   = 'approved';
                $data['approved_by'] = Auth::id();
                $data['approved_at'] = now();
            } else {
                $data['po_status'] = $data['po_status'] ?? 'draft';
            }

            $data['delivery_status']    = 'pending';
            $data['payment_status']     = 'unpaid';
            $data['qty_yet_to_receive'] = $data['quantity'] ?? 0;
            $data['balance_payable']    = 0;

            return PurchaseOrder::create($data);
        });
    }

    // ── Record GRN ────────────────────────────────────────────────────────────

    public function recordGoodsReceived(array $data): GoodsReceivedNote
    {
        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::findOrFail($data['purchase_order_id']);

            $data['po_number']       = $po->po_number;
            $data['supplier_id']     = $po->supplier_id;
            $data['supplier_name']   = $po->supplier_name;
            $data['grn_number']      = $this->generateGrnNumber();
            $data['unit_price']      = $po->unit_price;
            $data['unit_of_measure'] = $po->unit_of_measure ?? 'KG';
            $data['created_by']      = Auth::id();

            // Calculate values
            $receivedQty = (float) $data['received_quantity'];
            $expectedQty = (float) ($data['expected_quantity'] ?? $receivedQty);

            $data['accepted_quantity'] = $data['accepted_quantity'] ?? $receivedQty;
            $data['total_value']       = round($receivedQty * (float) $po->unit_price, 2);

            // Variance
            $variance    = $receivedQty - $expectedQty;
            $variancePct = $expectedQty > 0 ? round(($variance / $expectedQty) * 100, 4) : 0;

            $data['weight_variance']     = $variance;
            $data['variance_percentage'] = $variancePct;
            $data['variance_type']       = $variance < -0.001
                ? 'loss'
                : ($variance > 0.001 ? 'gain' : 'normal');

            // Auto-verify
            if (config('procurement.features.auto_verify_grn', true)) {
                $data['grn_status']  = 'verified';
                $data['verified_by'] = Auth::id();
                $data['verified_at'] = now();
            } else {
                $data['grn_status'] = 'draft';
            }

            $grn = GoodsReceivedNote::create($data);

            // Log variance if above threshold
            if (config('procurement.features.weight_variance_tracking', true)) {
                $threshold = (float) config('procurement.features.variance_threshold_pct', 0.5);

                if (abs($variancePct) > $threshold) {
                    GrnWeightVariance::create([
                        'grn_id'               => $grn->id,
                        'purchase_order_id'    => $po->id,
                        'variance_date'        => $data['grn_date'],
                        'expected_quantity'    => $expectedQty,
                        'received_quantity'    => $receivedQty,
                        'variance_quantity'    => $variance,
                        'variance_percentage'  => $variancePct,
                        'variance_type'        => $data['variance_type'],
                        'threshold_percentage' => $threshold,
                        'remarks'              => $data['variance_remarks'] ?? null,
                        'recorded_by'          => Auth::id(),
                        'created_at'           => now(),
                    ]);
                }
            }

            // Update PO totals
            $this->recalculatePOTotals($po);

            // Post supplier ledger entry
            if (
                config('procurement.features.supplier_ledger', true)
                && $grn->grn_status === 'verified'
            ) {
                $payableValue = $this->getGrnPayableValue($grn, $po);

                $this->postLedgerEntry([
                    'supplier_id'      => $po->supplier_id,
                    'transaction_date' => $grn->grn_date,
                    'transaction_type' => 'purchase',
                    'reference_type'   => 'GoodsReceivedNote',
                    'reference_id'     => $grn->id,
                    'reference_number' => $grn->grn_number,
                    'debit_amount'     => 0,
                    'credit_amount'    => $payableValue,
                    'description'      => 'GRN: ' . $grn->grn_number . ' for PO ' . $po->po_number,
                ]);
            }

            return $grn;
        });
    }

    // ── Record Payment ────────────────────────────────────────────────────────

    public function recordPayment(array $data): PurchasePayment
    {
        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::findOrFail($data['purchase_order_id']);

            $data['po_number']      = $po->po_number;
            $data['supplier_id']    = $po->supplier_id;
            $data['supplier_name']  = $po->supplier_name;
            $data['voucher_number'] = $this->generateVoucherNumber();
            $data['created_by']     = Auth::id();

            $amount       = (float) $data['amount_paid'];
            $newTotalPaid = (float) $po->total_paid + $amount;
            $payableBasis = $this->getPOPayableBasisAmount($po);

            // Auto-detect payment type
            if (!isset($data['payment_type'])) {
                $data['payment_type'] = ($payableBasis > 0 && $newTotalPaid > $payableBasis)
                    ? 'advance'
                    : 'regular';
            }

            // Auto-post
            if (
                config('procurement.features.auto_post_payment', true)
                && !config('procurement.features.payment_approval', false)
            ) {
                $data['status']    = 'posted';
                $data['is_posted'] = true;
            } else {
                $data['status']    = 'draft';
                $data['is_posted'] = false;
            }

            $payment = PurchasePayment::create($data);

            if ($payment->is_posted) {
                $this->recalculatePOTotals($po);

                if (config('procurement.features.supplier_ledger', true)) {
                    $this->postLedgerEntry([
                        'supplier_id'      => $po->supplier_id,
                        'transaction_date' => $payment->payment_date,
                        'transaction_type' => 'payment',
                        'reference_type'   => 'PurchasePayment',
                        'reference_id'     => $payment->id,
                        'reference_number' => $payment->voucher_number,
                        'debit_amount'     => $amount,
                        'credit_amount'    => 0,
                        'description'      => 'Payment: ' . $payment->voucher_number . ' | ' . $payment->payment_method,
                    ]);
                }
            }

            return $payment;
        });
    }

    // ── Recalculate PO Totals ─────────────────────────────────────────────────

    public function recalculatePOTotals(PurchaseOrder $po): void
    {
        $po->refresh();

        // Received totals from verified/posted GRNs
        $grns = $po->goodsReceivedNotes()
            ->whereIn('grn_status', ['verified', 'posted'])
            ->get();

        $totalReceivedQty   = $grns->sum('received_quantity');
        $totalReceivedValue = $grns->sum('total_value');

        // Payment totals from posted payments
        $totalPaid   = $po->allPayments()->where('is_posted', true)->sum('amount_paid');
        $advancePaid = $po->allPayments()
            ->where('is_posted', true)
            ->where('payment_type', 'advance')
            ->sum('amount_paid');

        // Payable basis
        $payableBasis = $this->getPOPayableBasisAmountFromGrns($po, $grns);

        $balancePayable  = max(0, $payableBasis - $totalPaid);
        $qtyYetToReceive = max(0, ((float) ($po->quantity ?? 0)) - (float) $totalReceivedQty);

        // Determine statuses
        $deliveryStatus = $this->computeDeliveryStatus($po, (float) $totalReceivedQty);
        $paymentStatus  = $this->computePaymentStatus((float) $payableBasis, (float) $totalPaid);
        $poStatus       = $this->computePOStatus($po, $deliveryStatus);

        $po->update([
            'total_received_qty'   => $totalReceivedQty,
            'total_received_value' => $totalReceivedValue,
            'total_paid'           => $totalPaid,
            'advance_paid'         => $advancePaid,
            'balance_payable'      => $balancePayable,
            'qty_yet_to_receive'   => $qtyYetToReceive,
            'delivery_status'      => $deliveryStatus,
            'payment_status'       => $paymentStatus,
            'po_status'            => $poStatus,
        ]);
    }

    // ── Payable Basis Calculation ─────────────────────────────────────────────

    public function getPOPayableBasisAmount(PurchaseOrder $po): float
    {
        $grns = $po->goodsReceivedNotes()
            ->whereIn('grn_status', ['verified', 'posted'])
            ->get();

        return $this->getPOPayableBasisAmountFromGrns($po, $grns);
    }

    private function getPOPayableBasisAmountFromGrns(PurchaseOrder $po, $grns): float
    {
        $total = 0.0;

        foreach ($grns as $grn) {
            $basis = $grn->payment_basis_override ?? $po->payment_basis;

            if ($basis === 'expected_qty') {
                $qty = $grn->expected_quantity ?? $grn->received_quantity;
            } else {
                $qty = $grn->received_quantity;
            }

            $total += (float) $qty * (float) $grn->unit_price;
        }

        return round($total, 2);
    }

    private function getGrnPayableValue(GoodsReceivedNote $grn, PurchaseOrder $po): float
    {
        $basis = $grn->payment_basis_override ?? $po->payment_basis;

        if ($basis === 'expected_qty') {
            $qty = $grn->expected_quantity ?? $grn->received_quantity;
        } else {
            $qty = $grn->received_quantity;
        }

        return round((float) $qty * (float) $grn->unit_price, 2);
    }

    // ── Status Helpers ────────────────────────────────────────────────────────

    private function computeDeliveryStatus(PurchaseOrder $po, float $totalReceivedQty): string
    {
        $ordered = (float) ($po->quantity ?? 0);

        if ($totalReceivedQty <= 0)                    return 'pending';
        if ($totalReceivedQty >= $ordered * 1.001)     return 'over_received';
        if ($ordered > 0 && $totalReceivedQty >= $ordered) return 'completed';

        return 'partial';
    }

    private function computePaymentStatus(float $payableBasis, float $totalPaid): string
    {
        if ($totalPaid <= 0)                                           return 'unpaid';
        if ($payableBasis > 0 && $totalPaid > $payableBasis * 1.001)  return 'overpaid';
        if ($payableBasis > 0 && $totalPaid >= $payableBasis)          return 'paid';

        return 'partial';
    }

    private function computePOStatus(PurchaseOrder $po, string $deliveryStatus): string
    {
        if (in_array($po->po_status, ['cancelled', 'closed'])) {
            return $po->po_status;
        }

        if (in_array($deliveryStatus, ['completed', 'over_received'])) {
            return 'completed';
        }

        if ($deliveryStatus === 'partial') {
            return 'partial';
        }

        return $po->po_status; // keep current (draft/submitted/approved)
    }

    // ── Supplier Ledger ───────────────────────────────────────────────────────

    public function postLedgerEntry(array $data): SupplierLedgerEntry
    {
        // Calculate running balance
        $lastBalance = SupplierLedgerEntry::where('supplier_id', $data['supplier_id'])
            ->orderByDesc('id')
            ->value('running_balance') ?? 0;

        $data['running_balance'] = (float) $lastBalance
            + (float) ($data['credit_amount'] ?? 0)
            - (float) ($data['debit_amount'] ?? 0);

        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['created_at'] = now();

        $entry = SupplierLedgerEntry::create($data);

        // Update supplier current_balance
        Supplier::where('id', $data['supplier_id'])
            ->update(['current_balance' => $data['running_balance']]);

        return $entry;
    }

    public function getSupplierBalance(int $supplierId): float
    {
        return (float) (SupplierLedgerEntry::where('supplier_id', $supplierId)
            ->orderByDesc('id')
            ->value('running_balance') ?? 0);
    }

    // ── PO Actions ───────────────────────────────────────────────────────────

    public function approvePurchaseOrder(PurchaseOrder $po): void
    {
        $po->update([
            'po_status'   => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    public function cancelPurchaseOrder(PurchaseOrder $po, string $reason = ''): void
    {
        $po->update([
            'po_status'        => 'cancelled',
            'rejection_reason' => $reason,
        ]);
    }

    public function closePurchaseOrder(PurchaseOrder $po): void
    {
        $po->update(['delivery_status' => 'closed']);
    }
}
