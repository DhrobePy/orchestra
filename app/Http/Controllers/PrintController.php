<?php

namespace App\Http\Controllers;

use App\Models\CreditOrder;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PrintController extends Controller
{
    /** Printable customer ledger statement */
    public function customerStatement(int $id, Request $request): Response
    {
        $customer = Customer::with('ledgerEntries')->findOrFail($id);

        $entries = $customer->ledgerEntries()
            ->when($request->from, fn ($q) => $q->whereDate('date', '>=', $request->from))
            ->when($request->to,   fn ($q) => $q->whereDate('date', '<=', $request->to))
            ->orderBy('id')
            ->get();

        $totalDebit  = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');
        $opening     = 0;  // Opening balance (first row's balance - first row's debit + first row's credit)
        $closing     = (float) ($entries->last()?->balance ?? 0);

        $period = match (true) {
            (bool) $request->from && (bool) $request->to => $request->from . ' to ' . $request->to,
            (bool) $request->from => 'From ' . $request->from,
            (bool) $request->to   => 'Up to ' . $request->to,
            default               => 'All transactions',
        };

        return response(
            view('print.customer-statement', compact(
                'customer', 'entries', 'totalDebit', 'totalCredit', 'opening', 'closing', 'period'
            ))->render()
        )->header('Content-Type', 'text/html');
    }

    /** Printable credit order invoice */
    public function creditOrderInvoice(int $id): Response
    {
        $order = CreditOrder::with([
            'customer', 'items.product', 'items.variant',
            'assignedBranch', 'approvedBy', 'statusHistory.changedBy',
        ])->findOrFail($id);

        return response(
            view('print.credit-order-invoice', compact('order'))->render()
        )->header('Content-Type', 'text/html');
    }

    /** CSV export of customer ledger */
    public function exportLedgerCsv(int $id, Request $request)
    {
        $customer = Customer::findOrFail($id);

        $entries = $customer->ledgerEntries()
            ->when($request->from, fn ($q) => $q->whereDate('date', '>=', $request->from))
            ->when($request->to,   fn ($q) => $q->whereDate('date', '<=', $request->to))
            ->orderBy('id')
            ->get();

        $filename = 'ledger-' . str($customer->name)->slug() . '-' . now()->format('Ymd') . '.csv';

        $callback = function () use ($customer, $entries) {
            $fh = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fputs($fh, "\xEF\xBB\xBF");

            // Title block
            fputcsv($fh, ['CUSTOMER LEDGER STATEMENT']);
            fputcsv($fh, ['Customer:', $customer->name]);
            fputcsv($fh, ['Company:',  $customer->company_name ?? '—']);
            fputcsv($fh, ['Phone:',    $customer->phone ?? '—']);
            fputcsv($fh, ['Printed:',  now()->format('d M Y H:i')]);
            fputcsv($fh, []);

            // Header
            fputcsv($fh, ['Date', 'Description', 'Type', 'Debit (BDT)', 'Credit (BDT)', 'Balance (BDT)', 'Reference Type', 'Reference ID']);

            // Rows
            foreach ($entries as $e) {
                fputcsv($fh, [
                    $e->date?->format('d/m/Y') ?? '',
                    $e->description,
                    $e->typeLabel(),
                    number_format((float) $e->debit, 2),
                    number_format((float) $e->credit, 2),
                    number_format((float) $e->balance, 2),
                    $e->reference_type ?? '',
                    $e->reference_id ?? '',
                ]);
            }

            // Totals
            fputcsv($fh, []);
            fputcsv($fh, [
                'TOTALS', '',  '',
                number_format($entries->sum('debit'), 2),
                number_format($entries->sum('credit'), 2),
                number_format((float) ($entries->last()?->balance ?? 0), 2),
            ]);

            // Credit summary
            fputcsv($fh, []);
            fputcsv($fh, ['Credit Limit',   number_format((float)$customer->credit_limit, 2)]);
            fputcsv($fh, ['Credit Used',    number_format((float)$customer->credit_balance, 2)]);
            fputcsv($fh, ['Available',      number_format(max(0, (float)$customer->credit_limit - (float)$customer->credit_balance), 2)]);

            fclose($fh);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
