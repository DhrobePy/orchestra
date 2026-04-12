<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Services\NotificationDispatcher;

class PurchaseOrderObserver
{
    public function created(PurchaseOrder $po): void
    {
        NotificationDispatcher::fire('po.created', [
            'po_number'  => $po->po_number ?? "PO-{$po->id}",
            'supplier'   => $po->supplier?->name ?? '—',
            'total'      => '৳ ' . number_format((float) $po->total_amount, 2),
            'created_by' => auth()->user()?->name ?? 'System',
        ]);
    }

    public function updated(PurchaseOrder $po): void
    {
        if ($po->wasChanged('po_status') && $po->po_status === 'approved') {
            NotificationDispatcher::fire('po.approved', [
                'po_number' => $po->po_number ?? "PO-{$po->id}",
                'supplier'  => $po->supplier?->name ?? '—',
                'total'     => '৳ ' . number_format((float) $po->total_amount, 2),
            ]);
        }
    }
}
