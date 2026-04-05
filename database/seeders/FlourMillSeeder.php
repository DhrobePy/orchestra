<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\Field;
use App\Models\Module;
use App\Models\Relationship;
use App\Services\DynamicMigrationService;
use App\Services\SchemaCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlourMillSeeder extends Seeder
{
    private DynamicMigrationService $migrator;

    // ─── Select field option sets ─────────────────────────────────────────────
    private const OPTIONS = [
        'unit'              => ['kg' => 'KG', 'mt' => 'MT (Metric Ton)', 'bag' => 'Bag', 'piece' => 'Piece', 'litre' => 'Litre'],
        'price_type'        => ['retail' => 'Retail', 'wholesale' => 'Wholesale', 'distributor' => 'Distributor', 'special' => 'Special'],
        'payment_method'    => ['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque', 'mobile' => 'Mobile Banking'],
        'status_payment'    => ['pending' => 'Pending', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
        'status_po'         => ['draft' => 'Draft', 'sent' => 'Sent', 'acknowledged' => 'Acknowledged', 'partial' => 'Partial Received', 'received' => 'Fully Received', 'cancelled' => 'Cancelled'],
        'status_grn'        => ['draft' => 'Draft', 'received' => 'Received', 'cancelled' => 'Cancelled'],
        'status_invoice'    => ['pending' => 'Pending', 'partial' => 'Partial Paid', 'paid' => 'Fully Paid', 'overdue' => 'Overdue', 'cancelled' => 'Cancelled'],
        'status_return'     => ['draft' => 'Draft', 'approved' => 'Approved', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
        'status_order'      => ['draft' => 'Draft', 'confirmed' => 'Confirmed', 'processing' => 'Processing', 'allocated' => 'Allocated', 'dispatched' => 'Dispatched', 'delivered' => 'Delivered', 'returned' => 'Returned', 'cancelled' => 'Cancelled'],
        'status_shipping'   => ['pending' => 'Pending', 'dispatched' => 'Dispatched', 'in_transit' => 'In Transit', 'delivered' => 'Delivered', 'failed' => 'Failed'],
        'tx_type'           => ['credit' => 'Credit (In)', 'debit' => 'Debit (Out)'],
        'vehicle_type'      => ['truck' => 'Truck', 'van' => 'Van', 'motorcycle' => 'Motorcycle', 'car' => 'Car', 'pickup' => 'Pickup', 'other' => 'Other'],
        'vehicle_status'    => ['active' => 'Active', 'maintenance' => 'Under Maintenance', 'inactive' => 'Inactive'],
        'document_type'     => ['license' => 'License', 'insurance' => 'Insurance', 'registration' => 'Registration', 'fitness' => 'Fitness Certificate', 'tax' => 'Tax Token', 'other' => 'Other'],
        'driver_status'     => ['available' => 'Available', 'on_trip' => 'On Trip', 'off_duty' => 'Off Duty', 'inactive' => 'Inactive'],
        'attendance_status' => ['present' => 'Present', 'absent' => 'Absent', 'leave' => 'On Leave', 'half_day' => 'Half Day'],
        'rental_status'     => ['active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
        'trip_status'       => ['planned' => 'Planned', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
        'transport_cat'     => ['fuel' => 'Fuel', 'maintenance' => 'Maintenance', 'toll' => 'Toll', 'parking' => 'Parking', 'driver_allowance' => 'Driver Allowance', 'repair' => 'Repair', 'other' => 'Other'],
        'production_status' => ['planned' => 'Planned', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
        'account_type'      => ['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expense'],
        'je_status'         => ['draft' => 'Draft', 'posted' => 'Posted', 'voided' => 'Voided'],
        'voucher_status'    => ['draft' => 'Draft', 'approved' => 'Approved', 'paid' => 'Paid', 'cancelled' => 'Cancelled'],
        'expense_status'    => ['draft' => 'Draft', 'submitted' => 'Submitted', 'approved' => 'Approved', 'rejected' => 'Rejected', 'paid' => 'Paid'],
        'bank_acct_type'    => ['current' => 'Current', 'savings' => 'Savings', 'fixed_deposit' => 'Fixed Deposit'],
        'transfer_status'   => ['pending' => 'Pending', 'completed' => 'Completed', 'failed' => 'Failed', 'cancelled' => 'Cancelled'],
        'eod_status'        => ['open' => 'Open', 'verified' => 'Verified', 'closed' => 'Closed'],
        'rule_type'         => ['percentage_discount' => 'Percentage Discount', 'fixed_discount' => 'Fixed Discount', 'special_price' => 'Special Price', 'bulk_discount' => 'Bulk Discount'],
        'condition_type'    => ['customer' => 'Customer', 'quantity' => 'Quantity', 'amount' => 'Amount', 'product' => 'Product', 'branch' => 'Branch', 'date_range' => 'Date Range'],
        'operator'          => ['eq' => '= Equal', 'gt' => '> Greater Than', 'gte' => '>= Greater or Equal', 'lt' => '< Less Than', 'lte' => '<= Less or Equal', 'in' => 'In List'],
        'action_type'       => ['discount_percent' => 'Discount %', 'discount_fixed' => 'Fixed Amount Discount', 'set_price' => 'Set Price'],
        'shipment_status'   => ['ordered' => 'Ordered', 'in_transit' => 'In Transit', 'at_port' => 'At Port', 'customs' => 'Customs Clearance', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'],
        'alert_type'        => ['price_high' => 'Price High', 'price_low' => 'Price Low', 'stock_low' => 'Stock Low', 'shipment_delayed' => 'Shipment Delayed'],
    ];

    private function opts(string $key): array
    {
        return self::OPTIONS[$key] ?? [];
    }

    public function run(): void
    {
        $this->migrator = app(DynamicMigrationService::class);

        // Wipe meta-records so the seeder is safely re-runnable
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Relationship::truncate();
        Field::withTrashed()->forceDelete();
        Entity::withTrashed()->forceDelete();
        Module::withTrashed()->forceDelete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Clear any stale cache before inserting
        app(SchemaCache::class)->flushAll();

        $this->seedProducts();
        $this->seedInventory();
        $this->seedSuppliers();
        $this->seedPurchasing();
        $this->seedSales();
        $this->seedBranches();
        $this->seedFleet();
        $this->seedProduction();
        $this->seedAccounting();
        $this->seedExpenses();
        $this->seedHR();
        $this->seedBanking();
        $this->seedCashManagement();
        $this->seedPricing();
        $this->seedCommodity();

        $this->warmCache();
    }

    private function warmCache(): void
    {
        $cache = app(SchemaCache::class);

        // Pre-populate the all-entities index (used by AppServiceProvider nav builder)
        $cache->getAllEntities();

        // Pre-populate per-entity and per-table caches used by DynamicRecordResource
        Entity::with(['fields', 'relationships.relatedEntity', 'module'])->get()
            ->each(function (Entity $entity) use ($cache) {
                $cache->getEntity($entity->id);
                $cache->getEntityByTable($entity->table_name);
                $cache->getFields($entity->id);
            });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────────

    private function entity(Module $module, string $name, string $table, string $titleField, array $fields): Entity
    {
        $entity = Entity::create([
            'module_id'   => $module->id,
            'name'        => $name,
            'table_name'  => $table,
            'title_field' => $titleField,
        ]);

        foreach ($fields as $i => $f) {
            Field::create(array_merge($f, [
                'entity_id'  => $entity->id,
                'sort_order' => $i + 1,
            ]));
        }

        $this->migrator->createTable($table, $fields);

        return $entity;
    }

    private function rel(Entity $from, string $name, string $type, Entity $to, string $fk, string $lk = 'id'): void
    {
        Relationship::create([
            'entity_id'         => $from->id,
            'name'              => $name,
            'type'              => $type,
            'related_entity_id' => $to->id,
            'foreign_key'       => $fk,
            'local_key'         => $lk,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 1 — Products
    // ─────────────────────────────────────────────────────────────────────────

    private function seedProducts(): void
    {
        $m = Module::create([
            'name'        => 'Products',
            'slug'        => 'products',
            'icon'        => 'heroicon-o-cube',
            'description' => 'Product catalogue: flour grades, variants, and branch pricing',
            'is_active'   => true,
        ]);

        $products = $this->entity($m, 'Products', 'products', 'name', [
            ['name' => 'name',        'label' => 'Product Name',  'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'sku',         'label' => 'SKU',           'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'description', 'label' => 'Description',   'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'unit',        'label' => 'Unit',          'type' => 'select',  'options' => $this->opts('unit'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'category',    'label' => 'Category',      'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'is_active',   'label' => 'Active',        'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $variants = $this->entity($m, 'Product Variants', 'product_variants', 'name', [
            ['name' => 'product_id',  'label' => 'Product',       'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'name',        'label' => 'Variant Name',  'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'sku',         'label' => 'SKU',           'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'unit',        'label' => 'Unit',          'type' => 'select',  'options' => $this->opts('unit'), 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'weight_kg',   'label' => 'Weight (kg)',   'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'is_active',   'label' => 'Active',        'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $prices = $this->entity($m, 'Product Prices', 'product_prices', 'price_type', [
            ['name' => 'product_id',  'label' => 'Product',       'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'variant_id',  'label' => 'Variant',       'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'branch_id',   'label' => 'Branch',        'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'price_type',  'label' => 'Price Type',    'type' => 'select',  'options' => $this->opts('price_type'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'price',       'label' => 'Price',         'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'effective_date','label' => 'Effective Date','type' => 'date',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $this->rel($products, 'variants', 'hasMany',    $variants, 'product_id');
        $this->rel($variants, 'product',  'belongsTo',  $products, 'product_id');
        $this->rel($products, 'prices',   'hasMany',    $prices,   'product_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 2 — Inventory
    // ─────────────────────────────────────────────────────────────────────────

    private function seedInventory(): void
    {
        $m = Module::create([
            'name'        => 'Inventory',
            'slug'        => 'inventory',
            'icon'        => 'heroicon-o-archive-box',
            'description' => 'Stock levels per product/variant/branch with reservation tracking',
            'is_active'   => true,
        ]);

        $this->entity($m, 'Inventory', 'inventory', 'product_id', [
            ['name' => 'product_id',    'label' => 'Product',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'variant_id',    'label' => 'Variant',          'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'branch_id',     'label' => 'Branch',           'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'quantity',      'label' => 'Quantity on Hand', 'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'reserved_qty',  'label' => 'Reserved Qty',     'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'unit',          'label' => 'Unit',             'type' => 'select',  'options' => $this->opts('unit'), 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'last_updated',  'label' => 'Last Updated',     'type' => 'datetime','is_required' => false, 'is_listed' => true,  'is_editable' => false],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 3 — Suppliers
    // ─────────────────────────────────────────────────────────────────────────

    private function seedSuppliers(): void
    {
        $m = Module::create([
            'name'        => 'Suppliers',
            'slug'        => 'suppliers',
            'icon'        => 'heroicon-o-truck',
            'description' => 'Supplier master, ledger, and payment tracking',
            'is_active'   => true,
        ]);

        $suppliers = $this->entity($m, 'Suppliers', 'suppliers', 'name', [
            ['name' => 'name',           'label' => 'Supplier Name',   'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'company_name',   'label' => 'Company',         'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'contact_person', 'label' => 'Contact Person',  'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'phone',          'label' => 'Phone',           'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'email',          'label' => 'Email',           'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'address',        'label' => 'Address',         'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'credit_limit',   'label' => 'Credit Limit',    'type' => 'number',  'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'payment_terms',  'label' => 'Payment Terms',   'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'is_active',      'label' => 'Active',          'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $ledger = $this->entity($m, 'Supplier Ledger', 'supplier_ledger', 'description', [
            ['name' => 'supplier_id',    'label' => 'Supplier',        'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'date',           'label' => 'Date',            'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'description',    'label' => 'Description',     'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'debit',          'label' => 'Debit',           'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'credit',         'label' => 'Credit',          'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'balance',        'label' => 'Balance',         'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'reference_type', 'label' => 'Reference Type',  'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => false],
            ['name' => 'reference_id',   'label' => 'Reference ID',    'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => false],
        ]);

        $payments = $this->entity($m, 'Supplier Payments', 'supplier_payments', 'reference', [
            ['name' => 'supplier_id',    'label' => 'Supplier',        'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'amount',         'label' => 'Amount',          'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'payment_date',   'label' => 'Payment Date',    'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'payment_method', 'label' => 'Payment Method',  'type' => 'select',  'options' => $this->opts('payment_method'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'reference',      'label' => 'Reference',       'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',          'label' => 'Notes',           'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'status',         'label' => 'Status',          'type' => 'select',  'options' => $this->opts('status_payment'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $allocations = $this->entity($m, 'Supplier Payment Allocations', 'supplier_payment_allocations', 'amount', [
            ['name' => 'payment_id',     'label' => 'Payment',         'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'invoice_id',     'label' => 'Invoice',         'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'amount',         'label' => 'Amount',          'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $this->rel($suppliers, 'ledger',      'hasMany',   $ledger,      'supplier_id');
        $this->rel($suppliers, 'payments',    'hasMany',   $payments,    'supplier_id');
        $this->rel($payments,  'allocations', 'hasMany',   $allocations, 'payment_id');
        $this->rel($payments,  'supplier',    'belongsTo', $suppliers,   'supplier_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 4 — Purchasing
    // ─────────────────────────────────────────────────────────────────────────

    private function seedPurchasing(): void
    {
        $m = Module::create([
            'name'        => 'Purchasing',
            'slug'        => 'purchasing',
            'icon'        => 'heroicon-o-shopping-cart',
            'description' => 'Purchase orders, GRNs, invoices, and returns',
            'is_active'   => true,
        ]);

        $po = $this->entity($m, 'Purchase Orders', 'purchase_orders', 'po_number', [
            ['name' => 'supplier_id',    'label' => 'Supplier',        'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'branch_id',      'label' => 'Branch',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'po_number',      'label' => 'PO Number',       'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'order_date',     'label' => 'Order Date',      'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'expected_date',  'label' => 'Expected Date',   'type' => 'date',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'status',         'label' => 'Status',          'type' => 'select',  'options' => $this->opts('status_po'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'subtotal',       'label' => 'Subtotal',        'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'tax_amount',     'label' => 'Tax',             'type' => 'number',  'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'discount',       'label' => 'Discount',        'type' => 'number',  'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'total',          'label' => 'Total',           'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'notes',          'label' => 'Notes',           'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $grn = $this->entity($m, 'Goods Received Notes', 'goods_received_notes', 'grn_number', [
            ['name' => 'po_id',          'label' => 'Purchase Order',  'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'grn_number',     'label' => 'GRN Number',      'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'received_date',  'label' => 'Received Date',   'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'branch_id',      'label' => 'Branch',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'status',         'label' => 'Status',          'type' => 'select',  'options' => $this->opts('status_grn'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',          'label' => 'Notes',           'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $grnItems = $this->entity($m, 'GRN Items', 'goods_received_items', 'product_id', [
            ['name' => 'grn_id',         'label' => 'GRN',             'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'product_id',     'label' => 'Product',         'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'variant_id',     'label' => 'Variant',         'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'ordered_qty',    'label' => 'Ordered Qty',     'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'received_qty',   'label' => 'Received Qty',    'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'unit_cost',      'label' => 'Unit Cost',       'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $invoice = $this->entity($m, 'Purchase Invoices', 'purchase_invoices', 'invoice_number', [
            ['name' => 'grn_id',         'label' => 'GRN',             'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'supplier_id',    'label' => 'Supplier',        'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'invoice_number', 'label' => 'Invoice Number',  'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'invoice_date',   'label' => 'Invoice Date',    'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'due_date',       'label' => 'Due Date',        'type' => 'date',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'subtotal',       'label' => 'Subtotal',        'type' => 'number',  'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'tax',            'label' => 'Tax',             'type' => 'number',  'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'total',          'label' => 'Total',           'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'status',         'label' => 'Status',          'type' => 'select',  'options' => $this->opts('status_invoice'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $invoiceItems = $this->entity($m, 'Purchase Invoice Items', 'purchase_invoice_items', 'product_id', [
            ['name' => 'invoice_id',     'label' => 'Invoice',         'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'product_id',     'label' => 'Product',         'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'variant_id',     'label' => 'Variant',         'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'quantity',       'label' => 'Quantity',        'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'unit_cost',      'label' => 'Unit Cost',       'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'subtotal',       'label' => 'Subtotal',        'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
        ]);

        $returns = $this->entity($m, 'Purchase Returns', 'purchase_returns', 'return_number', [
            ['name' => 'grn_id',         'label' => 'GRN',             'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'supplier_id',    'label' => 'Supplier',        'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'return_number',  'label' => 'Return Number',   'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'return_date',    'label' => 'Return Date',     'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'reason',         'label' => 'Reason',          'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'total',          'label' => 'Total',           'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'status',         'label' => 'Status',          'type' => 'select',  'options' => $this->opts('status_return'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $returnItems = $this->entity($m, 'Purchase Return Items', 'purchase_return_items', 'product_id', [
            ['name' => 'return_id',      'label' => 'Return',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'product_id',     'label' => 'Product',         'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'variant_id',     'label' => 'Variant',         'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'quantity',       'label' => 'Quantity',        'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'unit_cost',      'label' => 'Unit Cost',       'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'subtotal',       'label' => 'Subtotal',        'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
        ]);

        $this->rel($po,      'grns',         'hasMany',   $grn,         'po_id');
        $this->rel($grn,     'purchaseOrder','belongsTo', $po,          'po_id');
        $this->rel($grn,     'items',        'hasMany',   $grnItems,    'grn_id');
        $this->rel($grn,     'invoices',     'hasMany',   $invoice,     'grn_id');
        $this->rel($invoice, 'items',        'hasMany',   $invoiceItems,'invoice_id');
        $this->rel($returns, 'items',        'hasMany',   $returnItems, 'return_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 5 — Sales
    // ─────────────────────────────────────────────────────────────────────────

    private function seedSales(): void
    {
        $m = Module::create([
            'name'        => 'Sales',
            'slug'        => 'sales',
            'icon'        => 'heroicon-o-currency-dollar',
            'description' => 'Customers, credit orders, payments, allocations, and ledger',
            'is_active'   => true,
        ]);

        $customers = $this->entity($m, 'Customers', 'customers', 'name', [
            ['name' => 'name',            'label' => 'Customer Name',   'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'company_name',    'label' => 'Company',         'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'contact_person',  'label' => 'Contact Person',  'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'phone',           'label' => 'Phone',           'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'email',           'label' => 'Email',           'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'address',         'label' => 'Address',         'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'credit_limit',    'label' => 'Credit Limit',    'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'credit_balance',  'label' => 'Credit Balance',  'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'payment_terms',   'label' => 'Payment Terms',   'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'branch_id',       'label' => 'Branch',          'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'is_active',       'label' => 'Active',          'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $orders = $this->entity($m, 'Credit Orders', 'credit_orders', 'order_number', [
            ['name' => 'customer_id',    'label' => 'Customer',        'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'branch_id',      'label' => 'Branch',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'order_number',   'label' => 'Order Number',    'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'order_date',     'label' => 'Order Date',      'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'delivery_date',  'label' => 'Delivery Date',   'type' => 'date',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'status',         'label' => 'Status',          'type' => 'select',  'options' => $this->opts('status_order'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'subtotal',       'label' => 'Subtotal',        'type' => 'number',  'is_required' => false, 'is_listed' => false, 'is_editable' => false],
            ['name' => 'discount',       'label' => 'Discount',        'type' => 'number',  'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'tax',            'label' => 'Tax',             'type' => 'number',  'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'total',          'label' => 'Total',           'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'paid_amount',    'label' => 'Paid Amount',     'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'balance',        'label' => 'Balance Due',     'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'notes',          'label' => 'Notes',           'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $orderItems = $this->entity($m, 'Credit Order Items', 'credit_order_items', 'product_id', [
            ['name' => 'order_id',       'label' => 'Order',           'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'product_id',     'label' => 'Product',         'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'variant_id',     'label' => 'Variant',         'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'quantity',       'label' => 'Quantity',        'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'unit_price',     'label' => 'Unit Price',      'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'discount',       'label' => 'Discount',        'type' => 'number',  'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'subtotal',       'label' => 'Subtotal',        'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
        ]);

        $shipping = $this->entity($m, 'Order Shipping', 'credit_order_shipping', 'status', [
            ['name' => 'order_id',        'label' => 'Order',           'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'vehicle_id',      'label' => 'Vehicle',         'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'driver_id',       'label' => 'Driver',          'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'dispatch_date',   'label' => 'Dispatch Date',   'type' => 'datetime','is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'delivered_date',  'label' => 'Delivered Date',  'type' => 'datetime','is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'delivery_address','label' => 'Delivery Address','type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'status',          'label' => 'Status',          'type' => 'select',  'options' => $this->opts('status_shipping'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',           'label' => 'Notes',           'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $payments = $this->entity($m, 'Customer Payments', 'customer_payments', 'reference', [
            ['name' => 'customer_id',    'label' => 'Customer',        'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'branch_id',      'label' => 'Branch',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'payment_date',   'label' => 'Payment Date',    'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'amount',         'label' => 'Amount',          'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'payment_method', 'label' => 'Payment Method',  'type' => 'select',  'options' => $this->opts('payment_method'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'reference',      'label' => 'Reference',       'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',          'label' => 'Notes',           'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'status',         'label' => 'Status',          'type' => 'select',  'options' => $this->opts('status_payment'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $allocations = $this->entity($m, 'Payment Allocations', 'payment_allocations', 'amount', [
            ['name' => 'payment_id',     'label' => 'Payment',         'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'order_id',       'label' => 'Order',           'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'amount',         'label' => 'Amount',          'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $ledger = $this->entity($m, 'Customer Ledger', 'customer_ledger', 'description', [
            ['name' => 'customer_id',    'label' => 'Customer',        'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'date',           'label' => 'Date',            'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'description',    'label' => 'Description',     'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'debit',          'label' => 'Debit',           'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'credit',         'label' => 'Credit',          'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'balance',        'label' => 'Balance',         'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'reference_type', 'label' => 'Reference Type',  'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => false],
            ['name' => 'reference_id',   'label' => 'Reference ID',    'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => false],
        ]);

        $this->rel($customers, 'orders',      'hasMany',   $orders,      'customer_id');
        $this->rel($customers, 'payments',    'hasMany',   $payments,    'customer_id');
        $this->rel($customers, 'ledger',      'hasMany',   $ledger,      'customer_id');
        $this->rel($orders,    'items',       'hasMany',   $orderItems,  'order_id');
        $this->rel($orders,    'shipping',    'hasOne',    $shipping,    'order_id');
        $this->rel($payments,  'allocations', 'hasMany',   $allocations, 'payment_id');
        $this->rel($orders,    'customer',    'belongsTo', $customers,   'customer_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 6 — Branches
    // ─────────────────────────────────────────────────────────────────────────

    private function seedBranches(): void
    {
        $m = Module::create([
            'name'        => 'Branches',
            'slug'        => 'branches',
            'icon'        => 'heroicon-o-building-office',
            'description' => 'Branch master and branch-level petty cash accounts',
            'is_active'   => true,
        ]);

        $branches = $this->entity($m, 'Branches', 'branches', 'name', [
            ['name' => 'name',        'label' => 'Branch Name', 'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'code',        'label' => 'Code',        'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'address',     'label' => 'Address',     'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'phone',       'label' => 'Phone',       'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'manager_id',  'label' => 'Manager',     'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'is_active',   'label' => 'Active',      'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $pca = $this->entity($m, 'Branch Petty Cash Accounts', 'branch_petty_cash_accounts', 'name', [
            ['name' => 'branch_id',       'label' => 'Branch',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'name',            'label' => 'Account Name',    'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'opening_balance', 'label' => 'Opening Balance', 'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'current_balance', 'label' => 'Current Balance', 'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'is_active',       'label' => 'Active',          'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $pct = $this->entity($m, 'Branch Petty Cash Transactions', 'branch_petty_cash_transactions', 'description', [
            ['name' => 'account_id',  'label' => 'Account',     'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'date',        'label' => 'Date',        'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'amount',      'label' => 'Amount',      'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'type',        'label' => 'Type',        'type' => 'select',  'options' => $this->opts('tx_type'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'reference',   'label' => 'Reference',   'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'balance',     'label' => 'Balance',     'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
        ]);

        $this->rel($branches, 'pettyCashAccounts',     'hasMany', $pca, 'branch_id');
        $this->rel($pca,      'transactions',          'hasMany', $pct, 'account_id');
        $this->rel($pct,      'account',               'belongsTo', $pca, 'account_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 7 — Fleet
    // ─────────────────────────────────────────────────────────────────────────

    private function seedFleet(): void
    {
        $m = Module::create([
            'name'        => 'Fleet',
            'slug'        => 'fleet',
            'icon'        => 'heroicon-o-truck',
            'description' => 'Vehicles, drivers, trips, fuel, and transport expenses',
            'is_active'   => true,
        ]);

        $vehicles = $this->entity($m, 'Vehicles', 'vehicles', 'registration_number', [
            ['name' => 'registration_number', 'label' => 'Registration',  'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'make',                'label' => 'Make',          'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'model',               'label' => 'Model',         'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'year',                'label' => 'Year',          'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'type',                'label' => 'Type',          'type' => 'select',  'options' => $this->opts('vehicle_type'),   'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'branch_id',           'label' => 'Branch',        'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'status',              'label' => 'Status',        'type' => 'select',  'options' => $this->opts('vehicle_status'), 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'is_active',           'label' => 'Active',        'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $vehicleDocs = $this->entity($m, 'Vehicle Documents', 'vehicle_documents', 'document_type', [
            ['name' => 'vehicle_id',      'label' => 'Vehicle',         'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'document_type',   'label' => 'Document Type',   'type' => 'select',  'options' => $this->opts('document_type'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'document_number', 'label' => 'Document Number', 'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'issue_date',      'label' => 'Issue Date',      'type' => 'date',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'expiry_date',     'label' => 'Expiry Date',     'type' => 'date',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',           'label' => 'Notes',           'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $logbook = $this->entity($m, 'Vehicle Logbook', 'vehicle_logbook', 'date', [
            ['name' => 'vehicle_id',      'label' => 'Vehicle',         'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'date',            'label' => 'Date',            'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'start_odometer',  'label' => 'Start Odometer',  'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'end_odometer',    'label' => 'End Odometer',    'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'distance',        'label' => 'Distance (km)',   'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'driver_id',       'label' => 'Driver',          'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',           'label' => 'Notes',           'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $rentals = $this->entity($m, 'Vehicle Rentals', 'vehicle_rentals', 'rental_date', [
            ['name' => 'vehicle_id',   'label' => 'Vehicle',      'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'driver_id',    'label' => 'Driver',       'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'rental_date',  'label' => 'Rental Date',  'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'return_date',  'label' => 'Return Date',  'type' => 'date',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'rate',         'label' => 'Daily Rate',   'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'total',        'label' => 'Total',        'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'status',       'label' => 'Status',       'type' => 'select',  'options' => $this->opts('rental_status'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',        'label' => 'Notes',        'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $drivers = $this->entity($m, 'Drivers', 'drivers', 'name', [
            ['name' => 'name',            'label' => 'Name',            'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'license_number',  'label' => 'License Number',  'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'license_expiry',  'label' => 'License Expiry',  'type' => 'date',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'phone',           'label' => 'Phone',           'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'email',           'label' => 'Email',           'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'branch_id',       'label' => 'Branch',          'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'status',          'label' => 'Status',          'type' => 'select',  'options' => $this->opts('driver_status'), 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'is_active',       'label' => 'Active',          'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $attendance = $this->entity($m, 'Driver Attendance', 'driver_attendance', 'date', [
            ['name' => 'driver_id',  'label' => 'Driver',     'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'date',       'label' => 'Date',       'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'check_in',   'label' => 'Check In',   'type' => 'datetime','is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'check_out',  'label' => 'Check Out',  'type' => 'datetime','is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'status',     'label' => 'Status',     'type' => 'select',  'options' => $this->opts('attendance_status'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',      'label' => 'Notes',      'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $driverDocs = $this->entity($m, 'Driver Documents', 'driver_documents', 'document_type', [
            ['name' => 'driver_id',       'label' => 'Driver',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'document_type',   'label' => 'Document Type',   'type' => 'select',  'options' => $this->opts('document_type'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'document_number', 'label' => 'Document Number', 'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'issue_date',      'label' => 'Issue Date',      'type' => 'date',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'expiry_date',     'label' => 'Expiry Date',     'type' => 'date',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $trips = $this->entity($m, 'Trip Assignments', 'trip_assignments', 'trip_date', [
            ['name' => 'vehicle_id', 'label' => 'Vehicle',    'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'driver_id',  'label' => 'Driver',     'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'branch_id',  'label' => 'Branch',     'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'trip_date',  'label' => 'Trip Date',  'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'purpose',    'label' => 'Purpose',    'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'status',     'label' => 'Status',     'type' => 'select',  'options' => $this->opts('trip_status'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',      'label' => 'Notes',      'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $tripOrders = $this->entity($m, 'Trip Order Assignments', 'trip_order_assignments', 'trip_id', [
            ['name' => 'trip_id',    'label' => 'Trip',  'type' => 'integer', 'is_required' => true, 'is_listed' => true, 'is_editable' => true],
            ['name' => 'order_id',   'label' => 'Order', 'type' => 'integer', 'is_required' => true, 'is_listed' => true, 'is_editable' => true],
        ]);

        $transportExp = $this->entity($m, 'Transport Expenses', 'transport_expenses', 'description', [
            ['name' => 'vehicle_id',   'label' => 'Vehicle',     'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'driver_id',    'label' => 'Driver',      'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'date',         'label' => 'Date',        'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'category',     'label' => 'Category',    'type' => 'select',  'options' => $this->opts('transport_cat'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'amount',       'label' => 'Amount',      'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'description',  'label' => 'Description', 'type' => 'textarea','is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'reference',    'label' => 'Reference',   'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $fuel = $this->entity($m, 'Fuel Logs', 'fuel_logs', 'date', [
            ['name' => 'vehicle_id',     'label' => 'Vehicle',        'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'driver_id',      'label' => 'Driver',         'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'date',           'label' => 'Date',           'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'odometer',       'label' => 'Odometer',       'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'liters',         'label' => 'Liters',         'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'cost_per_liter', 'label' => 'Cost/Liter',     'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'total_cost',     'label' => 'Total Cost',     'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'station',        'label' => 'Fuel Station',   'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $this->rel($vehicles, 'documents',     'hasMany', $vehicleDocs,  'vehicle_id');
        $this->rel($vehicles, 'logbook',       'hasMany', $logbook,      'vehicle_id');
        $this->rel($vehicles, 'trips',         'hasMany', $trips,        'vehicle_id');
        $this->rel($vehicles, 'fuelLogs',      'hasMany', $fuel,         'vehicle_id');
        $this->rel($drivers,  'attendance',    'hasMany', $attendance,   'driver_id');
        $this->rel($drivers,  'documents',     'hasMany', $driverDocs,   'driver_id');
        $this->rel($trips,    'orderAssignments','hasMany',$tripOrders,  'trip_id');
        $this->rel($trips,    'vehicle',       'belongsTo',$vehicles,    'vehicle_id');
        $this->rel($trips,    'driver',        'belongsTo',$drivers,     'driver_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 8 — Production
    // ─────────────────────────────────────────────────────────────────────────

    private function seedProduction(): void
    {
        $m = Module::create([
            'name'        => 'Production',
            'slug'        => 'production',
            'icon'        => 'heroicon-o-cog-6-tooth',
            'description' => 'Flour mill production scheduling and output tracking',
            'is_active'   => true,
        ]);

        $this->entity($m, 'Production Schedule', 'production_schedule', 'planned_date', [
            ['name' => 'product_id',   'label' => 'Product',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'variant_id',   'label' => 'Variant',          'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'branch_id',    'label' => 'Branch',           'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'planned_date', 'label' => 'Planned Date',     'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'planned_qty',  'label' => 'Planned Qty (MT)', 'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'actual_qty',   'label' => 'Actual Qty (MT)',  'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'status',       'label' => 'Status',           'type' => 'select',  'options' => $this->opts('production_status'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',        'label' => 'Notes',            'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 9 — Accounting
    // ─────────────────────────────────────────────────────────────────────────

    private function seedAccounting(): void
    {
        $m = Module::create([
            'name'        => 'Accounting',
            'slug'        => 'accounting',
            'icon'        => 'heroicon-o-calculator',
            'description' => 'Chart of accounts, journal entries, transaction lines, debit vouchers',
            'is_active'   => true,
        ]);

        $coa = $this->entity($m, 'Chart of Accounts', 'chart_of_accounts', 'account_name', [
            ['name' => 'account_code', 'label' => 'Account Code', 'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'account_name', 'label' => 'Account Name', 'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'account_type', 'label' => 'Type',         'type' => 'select',  'options' => $this->opts('account_type'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'parent_id',    'label' => 'Parent',       'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'is_active',    'label' => 'Active',       'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $je = $this->entity($m, 'Journal Entries', 'journal_entries', 'entry_number', [
            ['name' => 'entry_number',   'label' => 'Entry Number',   'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'entry_date',     'label' => 'Entry Date',     'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'description',    'label' => 'Description',    'type' => 'textarea','is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'reference_type', 'label' => 'Reference Type', 'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => false],
            ['name' => 'reference_id',   'label' => 'Reference ID',   'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => false],
            ['name' => 'total_debit',    'label' => 'Total Debit',    'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'total_credit',   'label' => 'Total Credit',   'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'status',         'label' => 'Status',         'type' => 'select',  'options' => $this->opts('je_status'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $txLines = $this->entity($m, 'Transaction Lines', 'transaction_lines', 'description', [
            ['name' => 'journal_entry_id', 'label' => 'Journal Entry', 'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'account_id',       'label' => 'Account',       'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'description',      'label' => 'Description',   'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'debit',            'label' => 'Debit',         'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'credit',           'label' => 'Credit',        'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $dv = $this->entity($m, 'Debit Vouchers', 'debit_vouchers', 'voucher_number', [
            ['name' => 'voucher_number', 'label' => 'Voucher Number', 'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'voucher_date',   'label' => 'Voucher Date',   'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'payee',          'label' => 'Payee',          'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'account_id',     'label' => 'Account',        'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'amount',         'label' => 'Amount',         'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'description',    'label' => 'Description',    'type' => 'textarea','is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'reference',      'label' => 'Reference',      'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'status',         'label' => 'Status',         'type' => 'select',  'options' => $this->opts('voucher_status'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $this->rel($je,  'lines',   'hasMany',   $txLines, 'journal_entry_id');
        $this->rel($coa, 'entries', 'hasMany',   $txLines, 'account_id');
        $this->rel($txLines, 'journalEntry', 'belongsTo', $je,  'journal_entry_id');
        $this->rel($txLines, 'account',      'belongsTo', $coa, 'account_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 10 — Expenses
    // ─────────────────────────────────────────────────────────────────────────

    private function seedExpenses(): void
    {
        $m = Module::create([
            'name'        => 'Expenses',
            'slug'        => 'expenses',
            'icon'        => 'heroicon-o-banknotes',
            'description' => 'Expense categories, subcategories, and expense vouchers',
            'is_active'   => true,
        ]);

        $cats = $this->entity($m, 'Expense Categories', 'expense_categories', 'name', [
            ['name' => 'name',        'label' => 'Category Name', 'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'description', 'label' => 'Description',   'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'is_active',   'label' => 'Active',        'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $subcats = $this->entity($m, 'Expense Subcategories', 'expense_subcategories', 'name', [
            ['name' => 'category_id', 'label' => 'Category',         'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'name',        'label' => 'Subcategory Name', 'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'description', 'label' => 'Description',      'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'is_active',   'label' => 'Active',           'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $vouchers = $this->entity($m, 'Expense Vouchers', 'expense_vouchers', 'voucher_number', [
            ['name' => 'voucher_number',  'label' => 'Voucher Number',  'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'voucher_date',    'label' => 'Voucher Date',    'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'branch_id',       'label' => 'Branch',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'category_id',     'label' => 'Category',        'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'subcategory_id',  'label' => 'Subcategory',     'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'amount',          'label' => 'Amount',          'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'description',     'label' => 'Description',     'type' => 'textarea','is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'reference',       'label' => 'Reference',       'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'approved_by',     'label' => 'Approved By',     'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'status',          'label' => 'Status',          'type' => 'select',  'options' => $this->opts('expense_status'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $this->rel($cats,    'subcategories', 'hasMany',   $subcats,  'category_id');
        $this->rel($cats,    'vouchers',      'hasMany',   $vouchers, 'category_id');
        $this->rel($subcats, 'vouchers',      'hasMany',   $vouchers, 'subcategory_id');
        $this->rel($vouchers,'category',      'belongsTo', $cats,     'category_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 11 — HR
    // ─────────────────────────────────────────────────────────────────────────

    private function seedHR(): void
    {
        $m = Module::create([
            'name'        => 'HR',
            'slug'        => 'hr',
            'icon'        => 'heroicon-o-users',
            'description' => 'Departments, positions, and employee master records',
            'is_active'   => true,
        ]);

        $depts = $this->entity($m, 'Departments', 'departments', 'name', [
            ['name' => 'name',        'label' => 'Department Name', 'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'description', 'label' => 'Description',     'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'is_active',   'label' => 'Active',          'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $positions = $this->entity($m, 'Positions', 'positions', 'title', [
            ['name' => 'department_id', 'label' => 'Department',   'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'title',         'label' => 'Position Title','type' => 'text',   'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'description',   'label' => 'Description',   'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'is_active',     'label' => 'Active',        'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $employees = $this->entity($m, 'Employees', 'employees', 'name', [
            ['name' => 'employee_number','label' => 'Employee No',   'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'name',           'label' => 'Full Name',     'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'department_id',  'label' => 'Department',    'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'position_id',    'label' => 'Position',      'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'branch_id',      'label' => 'Branch',        'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'hire_date',      'label' => 'Hire Date',     'type' => 'date',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'salary',         'label' => 'Salary',        'type' => 'number',  'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'phone',          'label' => 'Phone',         'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'email',          'label' => 'Email',         'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'address',        'label' => 'Address',       'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'is_active',      'label' => 'Active',        'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $this->rel($depts,    'positions', 'hasMany',   $positions, 'department_id');
        $this->rel($depts,    'employees', 'hasMany',   $employees, 'department_id');
        $this->rel($positions,'employees', 'hasMany',   $employees, 'position_id');
        $this->rel($employees,'department','belongsTo', $depts,     'department_id');
        $this->rel($employees,'position',  'belongsTo', $positions, 'position_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 12 — Banking
    // ─────────────────────────────────────────────────────────────────────────

    private function seedBanking(): void
    {
        $m = Module::create([
            'name'        => 'Banking',
            'slug'        => 'banking',
            'icon'        => 'heroicon-o-building-library',
            'description' => 'Bank accounts, transactions, and inter-account transfers',
            'is_active'   => true,
        ]);

        $accounts = $this->entity($m, 'Bank Accounts', 'bank_accounts', 'account_name', [
            ['name' => 'bank_name',       'label' => 'Bank Name',       'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'account_name',    'label' => 'Account Name',    'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'account_number',  'label' => 'Account Number',  'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'branch_code',     'label' => 'Branch Code',     'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'account_type',    'label' => 'Account Type',    'type' => 'select',  'options' => $this->opts('bank_acct_type'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'opening_balance', 'label' => 'Opening Balance', 'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'current_balance', 'label' => 'Current Balance', 'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'is_active',       'label' => 'Active',          'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $txns = $this->entity($m, 'Bank Transactions', 'bank_transactions', 'description', [
            ['name' => 'account_id',      'label' => 'Account',         'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'transaction_date','label' => 'Date',            'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'description',     'label' => 'Description',     'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'debit',           'label' => 'Debit',           'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'credit',          'label' => 'Credit',          'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'balance',         'label' => 'Balance',         'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'reference_type',  'label' => 'Reference Type',  'type' => 'text',    'is_required' => false, 'is_listed' => false, 'is_editable' => false],
            ['name' => 'reference_id',    'label' => 'Reference ID',    'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => false],
        ]);

        $transfers = $this->entity($m, 'Bank Transfers', 'bank_tx_transfers', 'reference', [
            ['name' => 'from_account_id', 'label' => 'From Account',   'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'to_account_id',   'label' => 'To Account',     'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'transfer_date',   'label' => 'Transfer Date',  'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'amount',          'label' => 'Amount',         'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'reference',       'label' => 'Reference',      'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',           'label' => 'Notes',          'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'status',          'label' => 'Status',         'type' => 'select',  'options' => $this->opts('transfer_status'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $this->rel($accounts, 'transactions',  'hasMany',   $txns,     'account_id');
        $this->rel($txns,     'account',       'belongsTo', $accounts, 'account_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 13 — Cash Management
    // ─────────────────────────────────────────────────────────────────────────

    private function seedCashManagement(): void
    {
        $m = Module::create([
            'name'        => 'Cash Management',
            'slug'        => 'cash-management',
            'icon'        => 'heroicon-o-banknotes',
            'description' => 'Petty cash accounts, EOD verification, and daily cash summaries',
            'is_active'   => true,
        ]);

        $pca = $this->entity($m, 'Petty Cash Accounts', 'petty_cash_accounts', 'name', [
            ['name' => 'name',            'label' => 'Account Name',    'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'branch_id',       'label' => 'Branch',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'opening_balance', 'label' => 'Opening Balance', 'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'current_balance', 'label' => 'Current Balance', 'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'is_active',       'label' => 'Active',          'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $cvl = $this->entity($m, 'Cash Verification Log', 'cash_verification_log', 'verification_date', [
            ['name' => 'account_id',       'label' => 'Account',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'verification_date','label' => 'Verification Date','type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'expected_balance', 'label' => 'Expected Balance', 'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'actual_balance',   'label' => 'Actual Balance',   'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'difference',       'label' => 'Difference',       'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'verified_by',      'label' => 'Verified By',      'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',            'label' => 'Notes',            'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $eod = $this->entity($m, 'EOD Summary', 'eod_summary', 'date', [
            ['name' => 'branch_id',      'label' => 'Branch',          'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'date',           'label' => 'Date',            'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'opening_cash',   'label' => 'Opening Cash',    'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'total_sales',    'label' => 'Total Sales',     'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'total_receipts', 'label' => 'Total Receipts',  'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'total_expenses', 'label' => 'Total Expenses',  'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'closing_cash',   'label' => 'Closing Cash',    'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'verified_by',    'label' => 'Verified By',     'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'status',         'label' => 'Status',          'type' => 'select',  'options' => $this->opts('eod_status'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',          'label' => 'Notes',           'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $this->rel($pca, 'verificationLog', 'hasMany', $cvl, 'account_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 14 — Pricing
    // ─────────────────────────────────────────────────────────────────────────

    private function seedPricing(): void
    {
        $m = Module::create([
            'name'        => 'Pricing',
            'slug'        => 'pricing',
            'icon'        => 'heroicon-o-tag',
            'description' => 'Pricing rules with configurable conditions and actions',
            'is_active'   => true,
        ]);

        $rules = $this->entity($m, 'Pricing Rules', 'pricing_rules', 'name', [
            ['name' => 'name',        'label' => 'Rule Name',   'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
            ['name' => 'rule_type',   'label' => 'Rule Type',   'type' => 'select',  'options' => $this->opts('rule_type'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'is_active',   'label' => 'Active',      'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $conditions = $this->entity($m, 'Pricing Rule Conditions', 'pricing_rule_conditions', 'condition_type', [
            ['name' => 'rule_id',        'label' => 'Rule',           'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'condition_type', 'label' => 'Condition Type', 'type' => 'select',  'options' => $this->opts('condition_type'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'operator',       'label' => 'Operator',       'type' => 'select',  'options' => $this->opts('operator'),        'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'value',          'label' => 'Value',          'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $actions = $this->entity($m, 'Pricing Rule Actions', 'pricing_rule_actions', 'action_type', [
            ['name' => 'rule_id',     'label' => 'Rule',        'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'action_type', 'label' => 'Action Type', 'type' => 'select',  'options' => $this->opts('action_type'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'value',       'label' => 'Value',       'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'product_id',  'label' => 'Product',     'type' => 'integer', 'is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $this->rel($rules, 'conditions', 'hasMany',   $conditions, 'rule_id');
        $this->rel($rules, 'actions',    'hasMany',   $actions,    'rule_id');
        $this->rel($conditions, 'rule',  'belongsTo', $rules,      'rule_id');
        $this->rel($actions,    'rule',  'belongsTo', $rules,      'rule_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE 15 — Commodity
    // ─────────────────────────────────────────────────────────────────────────

    private function seedCommodity(): void
    {
        $m = Module::create([
            'name'        => 'Commodity',
            'slug'        => 'commodity',
            'icon'        => 'heroicon-o-globe-alt',
            'description' => 'Wheat shipment tracking, market data, and price alerts',
            'is_active'   => true,
        ]);

        $shipments = $this->entity($m, 'Wheat Shipments', 'wheat_shipments', 'shipment_number', [
            ['name' => 'shipment_number', 'label' => 'Shipment Number', 'type' => 'text',    'is_required' => true,  'is_listed' => true,  'is_editable' => false],
            ['name' => 'supplier_id',     'label' => 'Supplier',        'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'vessel_name',     'label' => 'Vessel Name',     'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'origin_country',  'label' => 'Origin Country',  'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'arrival_date',    'label' => 'Arrival Date',    'type' => 'date',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'quantity_mt',     'label' => 'Quantity (MT)',   'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'unit_cost',       'label' => 'Unit Cost (USD)', 'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'total_cost',      'label' => 'Total Cost',      'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'status',          'label' => 'Status',          'type' => 'select',  'options' => $this->opts('shipment_status'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
        ]);

        $positions = $this->entity($m, 'Shipment Positions', 'wheat_shipment_positions', 'shipment_id', [
            ['name' => 'shipment_id',  'label' => 'Shipment',  'type' => 'integer', 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'product_id',   'label' => 'Product',   'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'warehouse_id', 'label' => 'Warehouse', 'type' => 'integer', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'quantity',     'label' => 'Quantity',  'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'location',     'label' => 'Location',  'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
        ]);

        $this->entity($m, 'Wheat Market Data', 'wheat_market_data', 'date', [
            ['name' => 'date',         'label' => 'Date',           'type' => 'date',    'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'price_per_mt', 'label' => 'Price/MT (USD)', 'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'source',       'label' => 'Source',         'type' => 'text',    'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'notes',        'label' => 'Notes',          'type' => 'textarea','is_required' => false, 'is_listed' => false, 'is_editable' => true],
        ]);

        $this->entity($m, 'Wheat Alerts', 'wheat_alerts', 'alert_type', [
            ['name' => 'alert_type',        'label' => 'Alert Type',        'type' => 'select',  'options' => $this->opts('alert_type'), 'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'threshold_value',   'label' => 'Threshold Value',   'type' => 'number',  'is_required' => true,  'is_listed' => true,  'is_editable' => true],
            ['name' => 'current_value',     'label' => 'Current Value',     'type' => 'number',  'is_required' => false, 'is_listed' => true,  'is_editable' => false],
            ['name' => 'is_active',         'label' => 'Active',            'type' => 'boolean', 'is_required' => false, 'is_listed' => true,  'is_editable' => true],
            ['name' => 'last_triggered_at', 'label' => 'Last Triggered',    'type' => 'datetime','is_required' => false, 'is_listed' => true,  'is_editable' => false],
        ]);

        $this->rel($shipments, 'positions', 'hasMany',   $positions, 'shipment_id');
        $this->rel($positions, 'shipment',  'belongsTo', $shipments, 'shipment_id');
    }
}
