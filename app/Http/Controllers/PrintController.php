<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\CreditOrder;
use App\Models\Customer;
use App\Models\InvoiceTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PrintController extends Controller
{
    /** Printable customer ledger statement */
    public function customerStatement(int $id, Request $request): Response
    {
        $customer = Customer::with('ledgerEntries')->findOrFail($id);
        $company  = CompanySetting::get();

        $entries = $customer->ledgerEntries()
            ->when($request->from, fn ($q) => $q->whereDate('date', '>=', $request->from))
            ->when($request->to,   fn ($q) => $q->whereDate('date', '<=', $request->to))
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $totalDebit  = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');
        $opening     = 0;
        $closing     = (float) ($entries->last()?->balance ?? 0);

        $period = match (true) {
            (bool) $request->from && (bool) $request->to => $request->from . ' to ' . $request->to,
            (bool) $request->from => 'From ' . $request->from,
            (bool) $request->to   => 'Up to ' . $request->to,
            default               => 'All transactions',
        };

        return response(
            view('print.customer-statement', compact(
                'customer', 'company', 'entries', 'totalDebit', 'totalCredit', 'opening', 'closing', 'period'
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

        $company  = CompanySetting::get();
        $template = InvoiceTemplate::defaultFor('credit_order');
        $tpl      = $template?->getEffectiveConfig();
        $tplCss   = $template?->toCssVars();

        return response(
            view('print.credit-order-invoice', compact('order', 'company', 'tpl', 'tplCss'))->render()
        )->header('Content-Type', 'text/html');
    }

    /** Printable payment receipt */
    public function paymentReceipt(int $id): Response
    {
        $payment = \App\Models\CustomerPayment::with([
            'customer', 'bankAccount', 'branch',
            'allocations.order',
        ])->findOrFail($id);

        $company  = CompanySetting::get();
        $template = InvoiceTemplate::defaultFor('payment_receipt');
        $tpl      = $template?->getEffectiveConfig();
        $tplCss   = $template?->toCssVars();

        return response(
            view('print.payment-receipt', compact('payment', 'company', 'tpl', 'tplCss'))->render()
        )->header('Content-Type', 'text/html');
    }

    /** Preview a template with type-appropriate sample data */
    public function previewTemplate(InvoiceTemplate $template): Response
    {
        $company = CompanySetting::get();
        $tpl     = $template->getEffectiveConfig();
        $tplCss  = $template->toCssVars();

        $html = match ($template->type) {
            'payment_receipt'    => $this->previewReceipt($company, $tpl, $tplCss),
            'customer_statement' => $this->previewStatement($company, $tpl, $tplCss),
            default              => $this->previewInvoice($company, $tpl, $tplCss),
        };

        return response($html)->header('Content-Type', 'text/html');
    }

    private function previewInvoice($company, array $tpl, string $tplCss): string
    {
        $order = new CreditOrder([
            'order_number'   => 'INV-2026-00123',
            'order_date'     => now()->toDateString(),
            'delivery_date'  => now()->addDays(7)->toDateString(),
            'status'         => 'approved',
            'payment_status' => 'unpaid',
            'subtotal'       => 8500,
            'discount'       => 425,
            'total'          => 8075,
            'paid_amount'    => 0,
            'balance'        => 8075,
            'notes'          => 'Please ensure delivery to the main warehouse gate.',
            'payment_terms'  => 'Net 30',
        ]);
        $order->id = 0;

        $fakeCustomer = new \App\Models\Customer([
            'name' => 'Acme Trading Co. Ltd', 'company_name' => 'Acme Group',
            'phone' => '+880 171 000 0000', 'address' => '45 Commerce Road, Dhaka 1212',
        ]);
        $order->setRelation('customer', $fakeCustomer);
        $order->setRelation('items', collect([
            (object)['product' => (object)['name' => 'Premium Basmati Rice'],  'variant' => null, 'quantity' => 20, 'unit_price' => 220, 'discount' => 0,   'discount_type' => 'flat', 'subtotal' => 4400],
            (object)['product' => (object)['name' => 'Refined Sunflower Oil'], 'variant' => null, 'quantity' => 15, 'unit_price' => 195, 'discount' => 125, 'discount_type' => 'flat', 'subtotal' => 2800],
            (object)['product' => (object)['name' => 'Wheat Flour (50 kg)'],   'variant' => null, 'quantity' => 5,  'unit_price' => 260, 'discount' => 300, 'discount_type' => 'flat', 'subtotal' => 1300],
        ]));
        $order->setRelation('assignedBranch', new \App\Models\Branch(['name' => 'Dhaka Central Branch']));
        $order->setRelation('approvedBy', null);
        $order->setRelation('statusHistory', collect());

        return view('print.credit-order-invoice', compact('order', 'company', 'tpl', 'tplCss'))->render();
    }

    private function previewReceipt($company, array $tpl, string $tplCss): string
    {
        $payment = new \App\Models\CustomerPayment([
            'payment_date'   => now()->toDateString(),
            'amount'         => 12500.00,
            'payment_method' => 'bank_transfer',
            'reference'      => 'TXN-2026-88541',
            'notes'          => 'Payment for March outstanding invoices.',
            'status'         => 'confirmed',
        ]);
        $payment->id = 999;

        $fakeCustomer = new \App\Models\Customer([
            'name'         => 'Acme Trading Co. Ltd',
            'company_name' => 'Acme Group',
            'phone'        => '+880 171 000 0000',
            'address'      => '45 Commerce Road, Dhaka 1212',
        ]);
        $payment->setRelation('customer', $fakeCustomer);
        $payment->setRelation('bankAccount', (object)[
            'bank_name'    => 'Dutch-Bangla Bank Ltd',
            'account_name' => 'Orchestra ERP Account',
        ]);
        $payment->setRelation('branch', (object)['name' => 'Main Branch']);

        $fakeAllocations = collect([
            (object)[
                'amount' => 7500,
                'order'  => (object)['order_number' => 'INV-2026-00118', 'total' => 7500, 'paid_amount' => 7500, 'balance' => 0],
            ],
            (object)[
                'amount' => 5000,
                'order'  => (object)['order_number' => 'INV-2026-00119', 'total' => 8000, 'paid_amount' => 5000, 'balance' => 3000],
            ],
        ]);
        $payment->setRelation('allocations', $fakeAllocations);

        return view('print.payment-receipt', compact('payment', 'company', 'tpl', 'tplCss'))->render();
    }

    private function previewStatement($company, array $tpl, string $tplCss): string
    {
        $customer = new \App\Models\Customer([
            'name'          => 'Acme Trading Co. Ltd',
            'company_name'  => 'Acme Group',
            'phone'         => '+880 171 000 0000',
            'address'       => '45 Commerce Road, Dhaka 1212',
            'payment_terms' => 'NET30',
            'credit_limit'  => 150000,
            'credit_balance'=> 42500,
            'is_active'     => true,
        ]);
        $customer->id = 999;

        $entries = collect([
            (object)['date' => now()->subDays(30), 'description' => 'Opening Balance',          'debit' => 0,      'credit' => 0,     'balance' => 20000, 'reference_type' => null, 'reference_id' => null],
            (object)['date' => now()->subDays(25), 'description' => 'Sales Invoice INV-00118',  'debit' => 15000,  'credit' => 0,     'balance' => 35000, 'reference_type' => 'invoice', 'reference_id' => 118],
            (object)['date' => now()->subDays(20), 'description' => 'Payment Received',          'debit' => 0,      'credit' => 12500, 'balance' => 22500, 'reference_type' => 'payment', 'reference_id' => 55],
            (object)['date' => now()->subDays(15), 'description' => 'Sales Invoice INV-00121',  'debit' => 22000,  'credit' => 0,     'balance' => 44500, 'reference_type' => 'invoice', 'reference_id' => 121],
            (object)['date' => now()->subDays(8),  'description' => 'Payment Received',          'debit' => 0,      'credit' => 5000,  'balance' => 39500, 'reference_type' => 'payment', 'reference_id' => 56],
            (object)['date' => now()->subDays(2),  'description' => 'Sales Invoice INV-00125',  'debit' => 8500,   'credit' => 0,     'balance' => 48000, 'reference_type' => 'invoice', 'reference_id' => 125],
        ]);

        $totalDebit  = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');
        $opening     = 20000;
        $closing     = (float) $entries->last()->balance;
        $period      = now()->subDays(30)->format('d M Y') . ' to ' . now()->format('d M Y');

        return view('print.customer-statement', compact(
            'customer', 'company', 'entries', 'totalDebit', 'totalCredit',
            'opening', 'closing', 'period', 'tpl', 'tplCss'
        ))->render();
    }

    /** Printable expense voucher */
    public function expenseVoucher(int $id): Response
    {
        $voucher = \Illuminate\Support\Facades\DB::table('expense_vouchers')->find($id);
        abort_if(!$voucher, 404);

        $company = CompanySetting::get();

        // Resolve related records
        $branch = $voucher->branch_id
            ? \Illuminate\Support\Facades\DB::table('branches')->find($voucher->branch_id)
            : null;

        $category = $voucher->category_id
            ? \Illuminate\Support\Facades\DB::table('expense_categories')->find($voucher->category_id)
            : null;

        $subcategory = $voucher->subcategory_id
            ? \Illuminate\Support\Facades\DB::table('expense_subcategories')->find($voucher->subcategory_id)
            : null;

        $bankAccount = $voucher->bank_account_id
            ? \Illuminate\Support\Facades\DB::table('bank_accounts')->find($voucher->bank_account_id)
            : null;

        $approvedBy = $voucher->approved_by
            ? \Illuminate\Support\Facades\DB::table('employees')->find($voucher->approved_by)
            : null;

        return response(
            view('print.expense-voucher', compact(
                'voucher', 'company', 'branch', 'category',
                'subcategory', 'bankAccount', 'approvedBy'
            ))->render()
        )->header('Content-Type', 'text/html');
    }

    /** CSV export of customer ledger */
    public function exportLedgerCsv(int $id, Request $request)
    {
        $customer = Customer::findOrFail($id);
        $company  = CompanySetting::get();

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
            fputcsv($fh, [$company->company_name . ' — CUSTOMER LEDGER STATEMENT']);
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
