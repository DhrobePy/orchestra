<?php

namespace App\Services;

use App\Models\CustomReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportQueryBuilderService
{
    // ── Data-source registry ────────────────────────────────────────────────
    public static function sources(): array
    {
        return [
            'credit_orders' => [
                'label' => 'Sales Orders',
                'table' => 'credit_orders',
                'model' => \App\Models\CreditOrder::class,
                'columns' => [
                    'credit_orders.order_number'  => ['label' => 'Order #',          'type' => 'string'],
                    'credit_orders.order_date'     => ['label' => 'Order Date',       'type' => 'date'],
                    'credit_orders.status'         => ['label' => 'Status',           'type' => 'string'],
                    'credit_orders.payment_status' => ['label' => 'Payment Status',   'type' => 'string'],
                    'credit_orders.subtotal'       => ['label' => 'Subtotal',         'type' => 'currency'],
                    'credit_orders.discount'       => ['label' => 'Discount',         'type' => 'currency'],
                    'credit_orders.total'          => ['label' => 'Total',            'type' => 'currency'],
                    'credit_orders.paid_amount'    => ['label' => 'Paid',             'type' => 'currency'],
                    'credit_orders.balance'        => ['label' => 'Balance',          'type' => 'currency'],
                    'customers.name'               => ['label' => 'Customer Name',    'type' => 'string',   'join' => 'customers'],
                    'branches.name'                => ['label' => 'Branch',           'type' => 'string',   'join' => 'branches'],
                ],
                'filters' => [
                    'credit_orders.order_date'     => ['label' => 'Order Date',    'type' => 'date_range'],
                    'credit_orders.status'         => ['label' => 'Status',        'type' => 'select',
                        'options' => ['draft'=>'Draft','pending_approval'=>'Pending','approved'=>'Approved',
                                      'in_production'=>'In Production','delivered'=>'Delivered','cancelled'=>'Cancelled']],
                    'credit_orders.payment_status' => ['label' => 'Payment Status','type' => 'select',
                        'options' => ['unpaid'=>'Unpaid','partially_paid'=>'Partial','paid'=>'Paid']],
                ],
                'joins' => [
                    'customers' => ['table' => 'customers', 'first' => 'credit_orders.customer_id', 'second' => 'customers.id'],
                    'branches'  => ['table' => 'branches',  'first' => 'credit_orders.branch_id',   'second' => 'branches.id'],
                ],
            ],

            'customers' => [
                'label' => 'Customers',
                'table' => 'customers',
                'model' => \App\Models\Customer::class,
                'columns' => [
                    'customers.name'           => ['label' => 'Name',          'type' => 'string'],
                    'customers.company_name'   => ['label' => 'Company',       'type' => 'string'],
                    'customers.phone'          => ['label' => 'Phone',         'type' => 'string'],
                    'customers.email'          => ['label' => 'Email',         'type' => 'string'],
                    'customers.credit_limit'   => ['label' => 'Credit Limit',  'type' => 'currency'],
                    'customers.credit_balance' => ['label' => 'Credit Balance','type' => 'currency'],
                    'customers.is_active'      => ['label' => 'Active',        'type' => 'boolean'],
                    'branches.name'            => ['label' => 'Branch',        'type' => 'string', 'join' => 'branches'],
                ],
                'filters' => [
                    'customers.is_active' => ['label' => 'Status', 'type' => 'select',
                        'options' => ['1' => 'Active', '0' => 'Inactive']],
                ],
                'joins' => [
                    'branches' => ['table' => 'branches', 'first' => 'customers.branch_id', 'second' => 'branches.id'],
                ],
            ],

            'customer_payments' => [
                'label' => 'Customer Payments',
                'table' => 'customer_payments',
                'model' => \App\Models\CustomerPayment::class,
                'columns' => [
                    'customer_payments.payment_date'   => ['label' => 'Date',           'type' => 'date'],
                    'customer_payments.amount'         => ['label' => 'Amount',         'type' => 'currency'],
                    'customer_payments.payment_method' => ['label' => 'Method',         'type' => 'string'],
                    'customer_payments.reference'      => ['label' => 'Reference',      'type' => 'string'],
                    'customer_payments.status'         => ['label' => 'Status',         'type' => 'string'],
                    'customers.name'                   => ['label' => 'Customer',       'type' => 'string', 'join' => 'customers'],
                ],
                'filters' => [
                    'customer_payments.payment_date' => ['label' => 'Date Range', 'type' => 'date_range'],
                    'customer_payments.payment_method' => ['label' => 'Method',   'type' => 'select',
                        'options' => ['cash'=>'Cash','bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','mobile_banking'=>'Mobile']],
                ],
                'joins' => [
                    'customers' => ['table' => 'customers', 'first' => 'customer_payments.customer_id', 'second' => 'customers.id'],
                ],
            ],

            'customer_ledger' => [
                'label' => 'Customer Ledger',
                'table' => 'customer_ledger',
                'model' => \App\Models\CustomerLedger::class,
                'columns' => [
                    'customer_ledger.date'        => ['label' => 'Date',        'type' => 'date'],
                    'customer_ledger.description' => ['label' => 'Description', 'type' => 'string'],
                    'customer_ledger.debit'       => ['label' => 'Debit',       'type' => 'currency'],
                    'customer_ledger.credit'      => ['label' => 'Credit',      'type' => 'currency'],
                    'customer_ledger.balance'     => ['label' => 'Balance',     'type' => 'currency'],
                    'customers.name'              => ['label' => 'Customer',    'type' => 'string', 'join' => 'customers'],
                ],
                'filters' => [
                    'customer_ledger.date' => ['label' => 'Date Range', 'type' => 'date_range'],
                ],
                'joins' => [
                    'customers' => ['table' => 'customers', 'first' => 'customer_ledger.customer_id', 'second' => 'customers.id'],
                ],
            ],

            'purchase_orders' => [
                'label' => 'Purchase Orders',
                'table' => 'purchase_orders',
                'model' => \App\Models\PurchaseOrder::class,
                'columns' => [
                    'purchase_orders.po_number'          => ['label' => 'PO #',            'type' => 'string'],
                    'purchase_orders.po_date'            => ['label' => 'PO Date',          'type' => 'date'],
                    'purchase_orders.po_status'          => ['label' => 'Status',           'type' => 'string'],
                    'purchase_orders.total_order_value'  => ['label' => 'Order Value',      'type' => 'currency'],
                    'purchase_orders.total_paid'         => ['label' => 'Total Paid',       'type' => 'currency'],
                    'purchase_orders.balance_payable'    => ['label' => 'Balance Payable',  'type' => 'currency'],
                    'suppliers.company_name'             => ['label' => 'Supplier',         'type' => 'string', 'join' => 'suppliers'],
                    'branches.name'                      => ['label' => 'Branch',           'type' => 'string', 'join' => 'branches'],
                ],
                'filters' => [
                    'purchase_orders.po_date'  => ['label' => 'PO Date',  'type' => 'date_range'],
                    'purchase_orders.po_status'=> ['label' => 'Status',   'type' => 'select',
                        'options' => ['draft'=>'Draft','submitted'=>'Submitted','approved'=>'Approved','closed'=>'Closed']],
                ],
                'joins' => [
                    'suppliers' => ['table' => 'suppliers', 'first' => 'purchase_orders.supplier_id', 'second' => 'suppliers.id'],
                    'branches'  => ['table' => 'branches',  'first' => 'purchase_orders.branch_id',   'second' => 'branches.id'],
                ],
            ],

            'suppliers' => [
                'label' => 'Suppliers',
                'table' => 'suppliers',
                'model' => \App\Models\Supplier::class,
                'columns' => [
                    'suppliers.company_name'    => ['label' => 'Company',        'type' => 'string'],
                    'suppliers.contact_person'  => ['label' => 'Contact',        'type' => 'string'],
                    'suppliers.phone'           => ['label' => 'Phone',          'type' => 'string'],
                    'suppliers.email'           => ['label' => 'Email',          'type' => 'string'],
                    'suppliers.current_balance' => ['label' => 'Balance',        'type' => 'currency'],
                    'suppliers.status'          => ['label' => 'Status',         'type' => 'string'],
                ],
                'filters' => [
                    'suppliers.status' => ['label' => 'Status', 'type' => 'select',
                        'options' => ['active'=>'Active','inactive'=>'Inactive','blocked'=>'Blocked']],
                ],
                'joins' => [],
            ],

            'products' => [
                'label' => 'Products',
                'table' => 'products',
                'model' => \App\Models\Product::class,
                'columns' => [
                    'products.name'          => ['label' => 'Product Name',  'type' => 'string'],
                    'products.sku'           => ['label' => 'SKU',            'type' => 'string'],
                    'products.price'         => ['label' => 'Price',          'type' => 'currency'],
                    'products.cost_price'    => ['label' => 'Cost Price',     'type' => 'currency'],
                    'products.is_active'     => ['label' => 'Active',         'type' => 'boolean'],
                    'product_categories.name'=> ['label' => 'Category',       'type' => 'string', 'join' => 'product_categories'],
                ],
                'filters' => [
                    'products.is_active' => ['label' => 'Status', 'type' => 'select',
                        'options' => ['1' => 'Active', '0' => 'Inactive']],
                ],
                'joins' => [
                    'product_categories' => ['table' => 'product_categories', 'first' => 'products.category_id', 'second' => 'product_categories.id'],
                ],
            ],
        ];
    }

    // ── Build the query ─────────────────────────────────────────────────────
    public function buildQuery(CustomReport $report, array $filterValues = []): Builder
    {
        $sources = self::sources();
        $src     = $sources[$report->data_source] ?? null;

        if (! $src) {
            throw new \InvalidArgumentException("Unknown data source: {$report->data_source}");
        }

        $selectedFields = collect($report->columns ?? [])->pluck('field')->filter()->values()->toArray();
        if (empty($selectedFields)) {
            $selectedFields = array_keys($src['columns']);
        }

        // Determine which joins are needed
        $neededJoins = collect($selectedFields)
            ->map(fn ($f) => $src['columns'][$f]['join'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Also check filter fields for joins
        foreach ($filterValues as $field => $val) {
            $join = $src['columns'][$field]['join'] ?? null;
            if ($join && !in_array($join, $neededJoins)) {
                $neededJoins[] = $join;
            }
        }

        $query = DB::table($src['table']);

        // Apply left joins
        foreach ($neededJoins as $alias) {
            $j = $src['joins'][$alias] ?? null;
            if ($j) {
                $query->leftJoin($j['table'], $j['first'], '=', $j['second']);
            }
        }

        // Build SELECT
        $selects = array_map(fn ($f) => "$f as `" . str_replace('.', '__', $f) . "`", $selectedFields);
        $query->select($selects);

        // Apply filters
        foreach ($report->filters ?? [] as $filterDef) {
            $field = $filterDef['field'] ?? null;
            if (! $field) continue;

            $val = $filterValues[$field] ?? null;
            if ($val === null || $val === '') continue;

            $filterType = $filterDef['type'] ?? 'text';

            if ($filterType === 'date_range' && is_array($val)) {
                if ($val['from'] ?? null) $query->whereDate($field, '>=', $val['from']);
                if ($val['to']   ?? null) $query->whereDate($field, '<=', $val['to']);
            } elseif ($filterType === 'select') {
                $query->where($field, $val);
            } else {
                $query->where($field, 'like', "%{$val}%");
            }
        }

        // Sort
        if ($report->sort_by) {
            $query->orderBy($report->sort_by, $report->sort_dir ?? 'desc');
        }

        // Group by
        if ($report->group_by) {
            $query->groupBy($report->group_by);
        }

        return $query;
    }

    /** Normalize flat DB row into keyed column values for display. */
    public function formatRow(object $row, array $columns): array
    {
        $result = [];
        foreach ($columns as $col) {
            $key   = str_replace('.', '__', $col['field']);
            $value = $row->{$key} ?? null;

            $result[] = [
                'label' => $col['label'],
                'value' => $value,
                'type'  => $col['type'] ?? 'string',
            ];
        }
        return $result;
    }
}
