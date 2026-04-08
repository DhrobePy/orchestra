<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CreditOrder;
use App\Models\CreditOrderStatusHistory;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\CreditOrder\OrderApprovedNotification;
use App\Notifications\CreditOrder\OrderCancelledNotification;
use App\Notifications\CreditOrder\OrderDeliveredNotification;
use App\Notifications\CreditOrder\OrderShippedNotification;
use App\Notifications\CreditOrder\OrderSubmittedNotification;
use App\Notifications\CreditOrder\PaymentRecordedNotification;
use App\Notifications\CreditOrder\ReadyToShipNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditOrderWorkflowService
{
    // ── Submit ─────────────────────────────────────────────────────────────────
    // Sales exec/manager submits draft order.
    // Auto-checks credit and routes to pending_approval or escalated.

    public function submit(CreditOrder $order, ?string $notes = null): CreditOrder
    {
        $this->guard($order, [CreditOrder::STATUS_DRAFT], 'submit');

        $customer       = $order->customer;
        $orderTotal     = (float) $order->total;
        $creditLimit    = (float) ($customer->credit_limit ?? 0);
        $creditBalance  = (float) ($customer->credit_balance ?? 0);
        $available      = $creditLimit - $creditBalance;

        $newStatus = ($creditLimit > 0 && $orderTotal <= $available)
            ? CreditOrder::STATUS_PENDING_APPROVAL
            : CreditOrder::STATUS_ESCALATED;

        $metadata = [
            'credit_limit'    => $creditLimit,
            'credit_balance'  => $creditBalance,
            'available_credit'=> $available,
            'order_total'     => $orderTotal,
        ];

        DB::transaction(function () use ($order, $newStatus, $notes, $metadata) {
            $order->update(['status' => $newStatus]);
            $this->logHistory($order, CreditOrder::STATUS_DRAFT, $newStatus, $notes, $metadata);
        });

        // Notify accountants (and admins if escalated)
        $this->notifyRoles(
            $newStatus === CreditOrder::STATUS_ESCALATED
                ? ['filament_admin', 'super_admin', 'Accountant']
                : ['Accountant'],
            new OrderSubmittedNotification($order, $newStatus)
        );

        return $order->fresh();
    }

    // ── Approve ────────────────────────────────────────────────────────────────
    // Accountant approves pending_approval. Admin/superadmin approves escalated.

    public function approve(
        CreditOrder $order,
        ?int        $branchId      = null,
        ?int        $priority      = null,
        ?string     $deliveryDate  = null,
        ?string     $deliveryAddr  = null,
        ?string     $notes         = null,
    ): CreditOrder {
        $this->guard($order, [
            CreditOrder::STATUS_PENDING_APPROVAL,
            CreditOrder::STATUS_ESCALATED,
        ], 'approve');

        $from = $order->status;

        DB::transaction(function () use ($order, $from, $branchId, $priority, $deliveryDate, $deliveryAddr, $notes) {
            $update = [
                'status'      => CreditOrder::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ];

            if ($branchId)     $update['assigned_branch_id'] = $branchId;
            if ($priority)     $update['priority']           = $priority;
            if ($deliveryDate) $update['delivery_date']      = $deliveryDate;
            if ($deliveryAddr) $update['delivery_address']   = $deliveryAddr;

            $order->update($update);
            $this->logHistory($order, $from, CreditOrder::STATUS_APPROVED, $notes);
        });

        // Notify production managers + order creator
        $this->notifyRoles(
            ['Production Manager'],
            new OrderApprovedNotification($order)
        );
        $this->notifyUser($order->customer->id ?? null, new OrderApprovedNotification($order));

        return $order->fresh();
    }

    // ── Reject (cancel from approval stage) ───────────────────────────────────

    public function reject(CreditOrder $order, string $reason): CreditOrder
    {
        $this->guard($order, [
            CreditOrder::STATUS_PENDING_APPROVAL,
            CreditOrder::STATUS_ESCALATED,
        ], 'reject');

        $from = $order->status;

        DB::transaction(function () use ($order, $from, $reason) {
            $order->update([
                'status'              => CreditOrder::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
            ]);
            $this->logHistory($order, $from, CreditOrder::STATUS_CANCELLED, $reason);
        });

        $this->notifyOrderCreator($order, new OrderCancelledNotification($order, $reason));

        return $order->fresh();
    }

    // ── Request Cancellation ──────────────────────────────────────────────────
    // Sales exec/manager requests cancellation for approved/in-progress orders.

    public function requestCancellation(CreditOrder $order, string $reason): CreditOrder
    {
        $this->guard($order, [
            CreditOrder::STATUS_APPROVED,
            CreditOrder::STATUS_IN_PRODUCTION,
        ], 'request cancellation');

        $from = $order->status;

        DB::transaction(function () use ($order, $from, $reason) {
            $order->update([
                'status'                       => CreditOrder::STATUS_CANCELLATION_REQUESTED,
                'cancellation_requested_by'    => Auth::id(),
                'cancellation_requested_at'    => now(),
                'cancellation_reason'          => $reason,
            ]);
            $this->logHistory($order, $from, CreditOrder::STATUS_CANCELLATION_REQUESTED, $reason);
        });

        $this->notifyRoles(
            ['Accountant', 'filament_admin', 'super_admin'],
            new OrderCancelledNotification($order, $reason)
        );

        return $order->fresh();
    }

    // ── Approve Cancellation ──────────────────────────────────────────────────

    public function approveCancellation(CreditOrder $order, ?string $notes = null): CreditOrder
    {
        $this->guard($order, [CreditOrder::STATUS_CANCELLATION_REQUESTED], 'approve cancellation');

        DB::transaction(function () use ($order, $notes) {
            $order->update(['status' => CreditOrder::STATUS_CANCELLED]);
            $this->logHistory($order, CreditOrder::STATUS_CANCELLATION_REQUESTED, CreditOrder::STATUS_CANCELLED, $notes);
        });

        $this->notifyOrderCreator($order, new OrderCancelledNotification($order, $notes ?? 'Cancellation approved'));

        return $order->fresh();
    }

    // ── Reject Cancellation Request ───────────────────────────────────────────

    public function rejectCancellation(CreditOrder $order, ?string $notes = null, string $restoreTo = CreditOrder::STATUS_APPROVED): CreditOrder
    {
        $this->guard($order, [CreditOrder::STATUS_CANCELLATION_REQUESTED], 'reject cancellation');

        DB::transaction(function () use ($order, $notes, $restoreTo) {
            $order->update(['status' => $restoreTo]);
            $this->logHistory($order, CreditOrder::STATUS_CANCELLATION_REQUESTED, $restoreTo, $notes);
        });

        return $order->fresh();
    }

    // ── Start Production ──────────────────────────────────────────────────────

    public function startProduction(CreditOrder $order, ?string $notes = null): CreditOrder
    {
        $this->guard($order, [CreditOrder::STATUS_APPROVED], 'start production');

        DB::transaction(function () use ($order, $notes) {
            $order->update([
                'status'                 => CreditOrder::STATUS_IN_PRODUCTION,
                'production_started_by'  => Auth::id(),
                'production_started_at'  => now(),
            ]);
            $this->logHistory($order, CreditOrder::STATUS_APPROVED, CreditOrder::STATUS_IN_PRODUCTION, $notes);
        });

        return $order->fresh();
    }

    // ── Mark Ready to Ship ────────────────────────────────────────────────────

    public function markReadyToShip(CreditOrder $order, ?string $qcNotes = null): CreditOrder
    {
        $this->guard($order, [CreditOrder::STATUS_IN_PRODUCTION], 'mark ready to ship');

        DB::transaction(function () use ($order, $qcNotes) {
            $order->update([
                'status'       => CreditOrder::STATUS_READY_TO_SHIP,
                'qc_notes'     => $qcNotes,
                'qc_passed_at' => now(),
            ]);
            $this->logHistory($order, CreditOrder::STATUS_IN_PRODUCTION, CreditOrder::STATUS_READY_TO_SHIP, $qcNotes);
        });

        // Notify logistics managers
        $this->notifyRoles(
            ['Logistics Manager', 'Dispatcher'],
            new ReadyToShipNotification($order)
        );

        return $order->fresh();
    }

    // ── Dispatch (Ship) ───────────────────────────────────────────────────────
    // Triggered when dispatcher scans QR or clicks Dispatch.
    // This is the key accounting event — creates customer ledger entry.

    public function dispatch(CreditOrder $order, ?int $tripId = null, ?string $notes = null): CreditOrder
    {
        $this->guard($order, [CreditOrder::STATUS_READY_TO_SHIP], 'dispatch');

        DB::transaction(function () use ($order, $tripId, $notes) {
            $update = [
                'status'     => CreditOrder::STATUS_SHIPPED,
                'shipped_at' => now(),
            ];
            if ($tripId) $update['trip_id'] = $tripId;

            $order->update($update);
            $this->logHistory($order, CreditOrder::STATUS_READY_TO_SHIP, CreditOrder::STATUS_SHIPPED, $notes);

            // ── Accounting: Create customer ledger entry ──────────────────────
            $this->createLedgerEntry($order);

            // ── Update customer credit_balance ─────────────────────────────────
            $customer = $order->customer;
            if ($customer) {
                // COALESCE handles NULL credit_balance — increment() silently fails on NULL columns
                \DB::table('customers')
                    ->where('id', $customer->id)
                    ->update(['credit_balance' => \DB::raw('COALESCE(credit_balance, 0) + ' . (float) $order->total)]);
            }
        });

        $this->notifyRoles(
            ['Logistics Manager'],
            new OrderShippedNotification($order)
        );

        return $order->fresh();
    }

    // ── Confirm Delivery ──────────────────────────────────────────────────────

    public function confirmDelivery(CreditOrder $order, ?string $notes = null): CreditOrder
    {
        $this->guard($order, [CreditOrder::STATUS_SHIPPED], 'confirm delivery');

        DB::transaction(function () use ($order, $notes) {
            $order->update([
                'status'       => CreditOrder::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);
            $this->logHistory($order, CreditOrder::STATUS_SHIPPED, CreditOrder::STATUS_DELIVERED, $notes);
        });

        $this->notifyRoles(
            ['Accountant', 'filament_admin', 'super_admin'],
            new OrderDeliveredNotification($order)
        );

        return $order->fresh();
    }

    // ── Record Payment ────────────────────────────────────────────────────────

    public function recordPayment(
        CreditOrder $order,
        float       $amount,
        string      $method  = 'cash',
        ?string     $reference = null,
        ?string     $notes   = null,
    ): CreditOrder {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be positive.');
        }

        DB::transaction(function () use ($order, $amount, $method, $reference, $notes) {
            $newPaid    = (float) $order->paid_amount + $amount;
            $newBalance = max(0, (float) $order->total - $newPaid);

            $paymentStatus = $newBalance <= 0
                ? CreditOrder::PAYMENT_PAID
                : CreditOrder::PAYMENT_PARTIALLY_PAID;

            $order->update([
                'paid_amount'    => $newPaid,
                'balance'        => $newBalance,
                'payment_status' => $paymentStatus,
            ]);

            // Create customer_payments record
            $payment = \App\Models\CustomerPayment::create([
                'customer_id'    => $order->customer_id,
                'payment_date'   => now()->toDateString(),
                'amount'         => $amount,
                'payment_method' => $method,
                'reference'      => $reference,
                'notes'          => $notes,
                'status'         => \App\Models\CustomerPayment::STATUS_CONFIRMED,
            ]);

            // Create payment_allocations record
            \DB::table('payment_allocations')->insert([
                'payment_id' => $payment->id,
                'order_id'   => $order->id,
                'amount'     => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Customer ledger: credit entry (payment received)
            \DB::table('customer_ledger')->insert([
                'customer_id'    => $order->customer_id,
                'date'           => now()->toDateString(),
                'description'    => "Payment received — Order #{$order->order_number}",
                'debit'          => 0,
                'credit'         => $amount,
                'balance'        => $newBalance,
                'reference_type' => 'customer_payment',
                'reference_id'   => $payment->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Reduce customer credit_balance (COALESCE handles NULL)
            $customer = $order->customer;
            if ($customer) {
                \DB::table('customers')
                    ->where('id', $customer->id)
                    ->update(['credit_balance' => \DB::raw('COALESCE(credit_balance, 0) - ' . $amount)]);
            }

            $this->logHistory($order, $order->status, $order->status, $notes, [
                'payment_amount'  => $amount,
                'payment_method'  => $method,
                'payment_reference'=> $reference,
                'new_balance'     => $newBalance,
            ]);
        });

        $this->notifyRoles(
            ['filament_admin', 'super_admin'],
            new PaymentRecordedNotification($order, $amount)
        );

        return $order->fresh();
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function guard(CreditOrder $order, array $allowedStatuses, string $action): void
    {
        if (! in_array($order->status, $allowedStatuses)) {
            throw new \RuntimeException(
                "Cannot {$action} order #{$order->order_number}: current status is '{$order->status}'. "
                . 'Allowed: ' . implode(', ', $allowedStatuses)
            );
        }
    }

    private function logHistory(
        CreditOrder $order,
        ?string     $from,
        string      $to,
        ?string     $notes    = null,
        ?array      $metadata = null,
    ): void {
        CreditOrderStatusHistory::create([
            'credit_order_id' => $order->id,
            'from_status'     => $from,
            'to_status'       => $to,
            'changed_by'      => Auth::id(),
            'notes'           => $notes,
            'metadata'        => $metadata,
        ]);
    }

    private function createLedgerEntry(CreditOrder $order): void
    {
        try {
            $previousBalance = (float) \DB::table('customer_ledger')
                ->where('customer_id', $order->customer_id)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->value('balance') ?? 0;

            $newBalance = $previousBalance + (float) $order->total;

            \DB::table('customer_ledger')->insert([
                'customer_id'    => $order->customer_id,
                'date'           => now()->toDateString(),
                'description'    => "Credit Sale — Order #{$order->order_number}",
                'debit'          => (float) $order->total,
                'credit'         => 0,
                'balance'        => $newBalance,
                'reference_type' => 'credit_order',
                'reference_id'   => $order->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to create customer ledger entry: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
        }
    }

    private function notifyRoles(array $roleNames, $notification): void
    {
        try {
            $users = User::role($roleNames)->get();
            foreach ($users as $user) {
                $user->notify($notification);
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: ' . $e->getMessage());
        }
    }

    private function notifyUser(?int $userId, $notification): void
    {
        if (!$userId) return;
        try {
            $user = User::find($userId);
            $user?->notify($notification);
        } catch (\Throwable $e) {
            Log::error('User notification failed: ' . $e->getMessage());
        }
    }

    private function notifyOrderCreator(CreditOrder $order, $notification): void
    {
        // Notify the user who created the order (from status history - first entry)
        $firstEntry = $order->statusHistory()->orderBy('id')->first();
        if ($firstEntry?->changed_by) {
            $this->notifyUser($firstEntry->changed_by, $notification);
        }
    }
}
