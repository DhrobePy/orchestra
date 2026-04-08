<?php

namespace App\Services;

use App\Models\CreditOrder;
use App\Models\Customer;
use App\Models\CustomerPayment;
use Illuminate\Support\Facades\DB;

class CustomerPaymentService
{
    /**
     * Collect a payment from a customer.
     *
     * Auto-allocates to oldest outstanding orders (FIFO). If $orderId is given,
     * that order is settled first before any remaining amount spills to others.
     * If no outstanding orders exist (previous/initial dues), the payment is
     * recorded unallocated — it still reduces credit_balance and creates a
     * ledger credit entry, so the overall balance is correct.
     */
    public function collect(
        Customer $customer,
        float    $amount,
        string   $method              = 'cash',
        ?string  $reference           = null,
        ?string  $notes               = null,
        ?int     $orderId             = null,
        ?int     $branchId            = null,
        ?string  $paymentDate         = null,
        ?int     $bankAccountId       = null,
        bool     $skipAutoAllocation  = false,  // true = unallocated / previous due
    ): CustomerPayment {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be positive.');
        }

        return DB::transaction(function () use (
            $customer, $amount, $method, $reference, $notes,
            $orderId, $branchId, $paymentDate, $bankAccountId, $skipAutoAllocation
        ) {
            // 1. Create customer_payments record
            $payment = CustomerPayment::create([
                'customer_id'    => $customer->id,
                'branch_id'      => $branchId,
                'bank_account_id'=> $bankAccountId,
                'payment_date'   => $paymentDate ?? now()->toDateString(),
                'amount'         => $amount,
                'payment_method' => $method,
                'reference'      => $reference,
                'notes'          => $notes,
                'status'         => CustomerPayment::STATUS_CONFIRMED,
            ]);

            // 2. Determine orders to allocate against
            if ($skipAutoAllocation) {
                // Unallocated payment (previous due / opening balance) — no order touched
                $orders = collect();
            } else {
                $query = CreditOrder::where('customer_id', $customer->id)
                    ->whereIn('status', [
                        CreditOrder::STATUS_DELIVERED,
                        CreditOrder::STATUS_SHIPPED,
                        CreditOrder::STATUS_APPROVED,
                        CreditOrder::STATUS_IN_PRODUCTION,
                        CreditOrder::STATUS_READY_TO_SHIP,
                    ])
                    ->where('balance', '>', 0)
                    ->orderBy('created_at', 'asc');

                if ($orderId) {
                    // Specific invoice selected — settle it first, spill remainder to others
                    $specifiedOrder = CreditOrder::find($orderId);
                    $otherOrders    = (clone $query)->where('id', '!=', $orderId)->get();
                    $orders         = $specifiedOrder
                        ? collect([$specifiedOrder])->merge($otherOrders)
                        : $otherOrders;
                } else {
                    // Auto: oldest-first FIFO
                    $orders = $query->get();
                }
            }

            // 3. Allocate to orders (FIFO); any remainder = unallocated previous due
            $remaining = $amount;
            foreach ($orders as $order) {
                if ($remaining <= 0) break;

                $allocated  = min($remaining, (float) $order->balance);
                $newPaid    = (float) $order->paid_amount + $allocated;
                $newBalance = max(0, (float) $order->total - $newPaid);
                $payStatus  = $newBalance <= 0
                    ? CreditOrder::PAYMENT_PAID
                    : CreditOrder::PAYMENT_PARTIALLY_PAID;

                $order->update([
                    'paid_amount'    => $newPaid,
                    'balance'        => $newBalance,
                    'payment_status' => $payStatus,
                ]);

                DB::table('payment_allocations')->insert([
                    'payment_id' => $payment->id,
                    'order_id'   => $order->id,
                    'amount'     => $allocated,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $remaining -= $allocated;
            }

            // 4. Ledger credit entry (one entry for the full amount received)
            $prevBalance = (float) (DB::table('customer_ledger')
                ->where('customer_id', $customer->id)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->value('balance') ?? 0);

            $ledgerBalance = max(0, $prevBalance - $amount);

            $description = 'Payment received via ' . CustomerPayment::methodLabel($method);
            if ($reference) $description .= " — Ref: {$reference}";
            if ($notes)     $description .= " ({$notes})";

            DB::table('customer_ledger')->insert([
                'customer_id'    => $customer->id,
                'date'           => $payment->payment_date->toDateString(),
                'description'    => $description,
                'debit'          => 0,
                'credit'         => $amount,
                'balance'        => $ledgerBalance,
                'reference_type' => 'customer_payment',
                'reference_id'   => $payment->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // 5. Reduce customers.credit_balance (NULL-safe)
            DB::table('customers')
                ->where('id', $customer->id)
                ->update([
                    'credit_balance' => DB::raw('COALESCE(credit_balance, 0) - ' . $amount),
                ]);

            return $payment;
        });
    }

    /**
     * Record an opening / previous due for a customer who has balances
     * that pre-date Orchestra. Creates a debit ledger entry and increases
     * credit_balance — no credit order is involved.
     */
    public function recordOpeningBalance(
        Customer $customer,
        float    $amount,
        ?string  $description = null,
        ?string  $date        = null,
    ): void {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Opening balance must be positive.');
        }

        DB::transaction(function () use ($customer, $amount, $description, $date) {
            $prevBalance = (float) (DB::table('customer_ledger')
                ->where('customer_id', $customer->id)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->value('balance') ?? 0);

            $newBalance = $prevBalance + $amount;

            DB::table('customer_ledger')->insert([
                'customer_id'    => $customer->id,
                'date'           => $date ?? now()->toDateString(),
                'description'    => $description ?? 'Opening / previous balance',
                'debit'          => $amount,
                'credit'         => 0,
                'balance'        => $newBalance,
                'reference_type' => 'opening_balance',
                'reference_id'   => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::table('customers')
                ->where('id', $customer->id)
                ->update([
                    'credit_balance' => DB::raw('COALESCE(credit_balance, 0) + ' . $amount),
                ]);
        });
    }
}
