<?php

return [
    'features' => [
        'multi_item_po'              => false,  // single commodity per PO (flour mill default)
        'po_approval'                => false,  // PO requires approval before active
        'payment_approval'           => false,  // payments require approval
        'weight_variance_tracking'   => true,   // log GRN weight variances
        'variance_threshold_pct'     => 0.5,    // % above which variance is logged
        'purchase_invoices'          => false,  // formal purchase invoice sub-module
        'purchase_returns'           => false,  // purchase returns sub-module
        'multi_branch'               => true,   // multi-branch receiving
        'supplier_ledger'            => true,   // maintain supplier ledger
        'auto_verify_grn'            => true,   // auto-verify GRN on creation
        'auto_post_payment'          => true,   // auto-post payment on creation
        'cheque_tracking'            => false,  // track cheque clearance status
        'foreign_currency'           => false,  // multi-currency support
    ],
    'defaults' => [
        'payment_basis'          => 'received_qty', // 'received_qty' or 'expected_qty'
        'currency'               => 'BDT',
        'po_number_prefix'       => 'PO',
        'grn_number_prefix'      => 'GRN',
        'payment_voucher_prefix' => 'PV',
        'return_number_prefix'   => 'PR',
        'auto_approve_po'        => true, // auto-approve PO on creation when po_approval=false
    ],
    'origins' => [
        'canada'    => 'Canada',
        'russia'    => 'Russia',
        'australia' => 'Australia',
        'ukraine'   => 'Ukraine',
        'india'     => 'India',
        'local'     => 'Local',
        'brazil'    => 'Brazil',
        'other'     => 'Other',
    ],
    'uom' => ['KG', 'MT', 'TON', 'BAG', 'PCS', 'LITRE', 'UNIT'],
];
