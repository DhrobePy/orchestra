<?php

namespace Database\Seeders;

use App\Models\InvoiceTemplate;
use Illuminate\Database\Seeder;

class InvoiceTemplateSeeder extends Seeder
{
    /**
     * Seed international invoice preset templates.
     * Uses updateOrCreate so it's safe to re-run without creating duplicates.
     */
    public function run(): void
    {
        foreach (self::presets() as $preset) {
            InvoiceTemplate::updateOrCreate(
                ['name' => $preset['name'], 'type' => $preset['type']],
                [
                    'is_default' => $preset['is_default'] ?? false,
                    'config'     => $preset['config'],
                ]
            );
        }
    }

    public static function presets(): array
    {
        return [

            // ── 1. Classic International ────────────────────────────────────
            // Deep navy header, gold accent. Widely recognised in global trade.
            [
                'name'       => '🌐 Classic International',
                'type'       => 'credit_order',
                'is_default' => true,
                'config'     => [
                    'paper_size'              => 'A4',
                    'orientation'             => 'portrait',
                    'font_family'             => 'Arial, Helvetica, sans-serif',
                    'primary_color'           => '#1e3a5f',
                    'accent_color'            => '#c9a84c',
                    'text_color'              => '#1a1a2e',
                    'border_color'            => '#dce3ec',
                    'header_bg'               => '#1e3a5f',
                    'header_text_color'       => '#ffffff',
                    'logo_position'           => 'left',
                    'show_company_address'    => true,
                    'show_company_contact'    => true,
                    'header_text'             => '',
                    'footer_text'             => 'Thank you for your business. Payment due as per agreed terms.',
                    'show_page_numbers'       => false,
                    'watermark_text'          => '',
                    'show_item_code'          => false,
                    'show_description'        => true,
                    'show_discount_column'    => true,
                    'show_tax_column'         => false,
                    'show_subtotal'           => true,
                    'show_discount_total'     => true,
                    'show_tax_total'          => false,
                    'show_payment_terms'      => true,
                    'show_notes'              => true,
                    'show_terms'              => false,
                    'terms_text'             => '',
                    'show_bank_details'       => true,
                    'custom_header_fields'    => [],
                ],
            ],

            // ── 2. UK / European VAT Invoice ────────────────────────────────
            // Charcoal header, teal accent, shows VAT column. Compliant with
            // UK & EU invoice requirements (tax line visible, payment terms prominent).
            [
                'name' => '🇬🇧 UK / European VAT',
                'type' => 'credit_order',
                'config' => [
                    'paper_size'              => 'A4',
                    'orientation'             => 'portrait',
                    'font_family'             => 'Georgia, "Times New Roman", serif',
                    'primary_color'           => '#1f3640',
                    'accent_color'            => '#00897b',
                    'text_color'              => '#212121',
                    'border_color'            => '#cfd8dc',
                    'header_bg'               => '#1f3640',
                    'header_text_color'       => '#ffffff',
                    'logo_position'           => 'left',
                    'show_company_address'    => true,
                    'show_company_contact'    => true,
                    'header_text'             => '',
                    'footer_text'             => 'VAT Registered. This invoice is subject to applicable tax laws.',
                    'show_page_numbers'       => true,
                    'watermark_text'          => '',
                    'show_item_code'          => true,
                    'show_description'        => true,
                    'show_discount_column'    => true,
                    'show_tax_column'         => true,
                    'show_subtotal'           => true,
                    'show_discount_total'     => true,
                    'show_tax_total'          => true,
                    'show_payment_terms'      => true,
                    'show_notes'              => true,
                    'show_terms'              => true,
                    'terms_text'              => "Payment is due within the agreed credit period.\nLate payments may incur interest charges under the Late Payment of Commercial Debts Act.\nGoods remain the property of the seller until paid in full.",
                    'show_bank_details'       => true,
                    'custom_header_fields'    => [
                        ['label' => 'VAT Reg. No.', 'value' => ''],
                        ['label' => 'Company Reg.', 'value' => ''],
                    ],
                ],
            ],

            // ── 3. US Letter — Corporate Blue ───────────────────────────────
            // US Letter size, electric blue + orange. Clean American-style layout.
            [
                'name' => '🇺🇸 US Letter — Corporate',
                'type' => 'credit_order',
                'config' => [
                    'paper_size'              => 'Letter',
                    'orientation'             => 'portrait',
                    'font_family'             => '"Trebuchet MS", sans-serif',
                    'primary_color'           => '#1565c0',
                    'accent_color'            => '#e65100',
                    'text_color'              => '#212121',
                    'border_color'            => '#e3f2fd',
                    'header_bg'               => '#1565c0',
                    'header_text_color'       => '#ffffff',
                    'logo_position'           => 'right',
                    'show_company_address'    => true,
                    'show_company_contact'    => true,
                    'header_text'             => '',
                    'footer_text'             => 'Questions? Contact us at the address above. Thank you for your business!',
                    'show_page_numbers'       => true,
                    'watermark_text'          => '',
                    'show_item_code'          => true,
                    'show_description'        => true,
                    'show_discount_column'    => true,
                    'show_tax_column'         => true,
                    'show_subtotal'           => true,
                    'show_discount_total'     => true,
                    'show_tax_total'          => true,
                    'show_payment_terms'      => true,
                    'show_notes'              => true,
                    'show_terms'              => false,
                    'terms_text'              => '',
                    'show_bank_details'       => true,
                    'custom_header_fields'    => [
                        ['label' => 'Federal EIN', 'value' => ''],
                        ['label' => 'PO Number',   'value' => ''],
                    ],
                ],
            ],

            // ── 4. Modern Minimalist ────────────────────────────────────────
            // White space-forward design. Light grey accents, no heavy header bar.
            // Popular in creative agencies and modern B2B.
            [
                'name' => '✨ Modern Minimalist',
                'type' => 'credit_order',
                'config' => [
                    'paper_size'              => 'A4',
                    'orientation'             => 'portrait',
                    'font_family'             => 'Arial, Helvetica, sans-serif',
                    'primary_color'           => '#111827',
                    'accent_color'            => '#6366f1',
                    'text_color'              => '#374151',
                    'border_color'            => '#f3f4f6',
                    'header_bg'               => '#111827',
                    'header_text_color'       => '#f9fafb',
                    'logo_position'           => 'center',
                    'show_company_address'    => true,
                    'show_company_contact'    => true,
                    'header_text'             => '',
                    'footer_text'             => 'We appreciate your business.',
                    'show_page_numbers'       => false,
                    'watermark_text'          => '',
                    'show_item_code'          => false,
                    'show_description'        => true,
                    'show_discount_column'    => false,
                    'show_tax_column'         => false,
                    'show_subtotal'           => true,
                    'show_discount_total'     => true,
                    'show_tax_total'          => false,
                    'show_payment_terms'      => true,
                    'show_notes'              => true,
                    'show_terms'              => false,
                    'terms_text'              => '',
                    'show_bank_details'       => true,
                    'custom_header_fields'    => [],
                ],
            ],

            // ── 5. Bold Executive ───────────────────────────────────────────
            // Rich dark slate with amber highlights. Heavyweight impression for
            // high-value B2B contracts and large enterprise clients.
            [
                'name' => '💼 Bold Executive',
                'type' => 'credit_order',
                'config' => [
                    'paper_size'              => 'A4',
                    'orientation'             => 'portrait',
                    'font_family'             => 'Arial, Helvetica, sans-serif',
                    'primary_color'           => '#0f172a',
                    'accent_color'            => '#f59e0b',
                    'text_color'              => '#0f172a',
                    'border_color'            => '#e2e8f0',
                    'header_bg'               => '#0f172a',
                    'header_text_color'       => '#fbbf24',
                    'logo_position'           => 'left',
                    'show_company_address'    => true,
                    'show_company_contact'    => true,
                    'header_text'             => '',
                    'footer_text'             => 'This invoice is electronically generated and is valid without signature.',
                    'show_page_numbers'       => true,
                    'watermark_text'          => '',
                    'show_item_code'          => true,
                    'show_description'        => true,
                    'show_discount_column'    => true,
                    'show_tax_column'         => false,
                    'show_subtotal'           => true,
                    'show_discount_total'     => true,
                    'show_tax_total'          => false,
                    'show_payment_terms'      => true,
                    'show_notes'              => true,
                    'show_terms'              => true,
                    'terms_text'              => "All invoices are due within the agreed credit terms.\nDisputes must be raised in writing within 7 days of invoice date.\nTitle to goods remains with the seller until full payment is received.",
                    'show_bank_details'       => true,
                    'custom_header_fields'    => [
                        ['label' => 'Contract Ref.', 'value' => ''],
                    ],
                ],
            ],

            // ── 6. South / Southeast Asia Trade ─────────────────────────────
            // Green & gold tones common in South Asian commerce. Shows all
            // discount, notes and bank details. A4 standard.
            [
                'name' => '🌿 South Asia Trade',
                'type' => 'credit_order',
                'config' => [
                    'paper_size'              => 'A4',
                    'orientation'             => 'portrait',
                    'font_family'             => 'Arial, Helvetica, sans-serif',
                    'primary_color'           => '#145a32',
                    'accent_color'            => '#d4ac0d',
                    'text_color'              => '#1b2631',
                    'border_color'            => '#d5e8d4',
                    'header_bg'               => '#145a32',
                    'header_text_color'       => '#f9e79f',
                    'logo_position'           => 'left',
                    'show_company_address'    => true,
                    'show_company_contact'    => true,
                    'header_text'             => '',
                    'footer_text'             => 'ধন্যবাদ / Thank you for your valued business.',
                    'show_page_numbers'       => false,
                    'watermark_text'          => '',
                    'show_item_code'          => false,
                    'show_description'        => true,
                    'show_discount_column'    => true,
                    'show_tax_column'         => false,
                    'show_subtotal'           => true,
                    'show_discount_total'     => true,
                    'show_tax_total'          => false,
                    'show_payment_terms'      => true,
                    'show_notes'              => true,
                    'show_terms'              => false,
                    'terms_text'              => '',
                    'show_bank_details'       => true,
                    'custom_header_fields'    => [
                        ['label' => 'BIN / TIN',  'value' => ''],
                        ['label' => 'Mushak No.', 'value' => ''],
                    ],
                ],
            ],

            // ── Receipt: Clean Receipt ──────────────────────────────────────
            [
                'name'       => '🧾 Clean Receipt',
                'type'       => 'payment_receipt',
                'is_default' => true,
                'config'     => [
                    'paper_size'              => 'A4',
                    'orientation'             => 'portrait',
                    'font_family'             => 'Arial, Helvetica, sans-serif',
                    'primary_color'           => '#065f46',
                    'accent_color'            => '#10b981',
                    'text_color'              => '#111827',
                    'border_color'            => '#d1fae5',
                    'header_bg'               => '#065f46',
                    'header_text_color'       => '#ffffff',
                    'logo_position'           => 'left',
                    'show_company_address'    => true,
                    'show_company_contact'    => true,
                    'header_text'             => 'OFFICIAL PAYMENT RECEIPT',
                    'footer_text'             => 'This is a computer-generated receipt. No signature required.',
                    'show_page_numbers'       => false,
                    'watermark_text'          => '',
                    'show_item_code'          => false,
                    'show_description'        => true,
                    'show_discount_column'    => false,
                    'show_tax_column'         => false,
                    'show_subtotal'           => true,
                    'show_discount_total'     => false,
                    'show_tax_total'          => false,
                    'show_payment_terms'      => false,
                    'show_notes'              => true,
                    'show_terms'              => false,
                    'terms_text'              => '',
                    'show_bank_details'       => false,
                    'custom_header_fields'    => [],
                ],
            ],

            // ── Statement: Classic Statement ────────────────────────────────
            [
                'name'       => '📋 Classic Statement',
                'type'       => 'customer_statement',
                'is_default' => true,
                'config'     => [
                    'paper_size'              => 'A4',
                    'orientation'             => 'portrait',
                    'font_family'             => 'Arial, Helvetica, sans-serif',
                    'primary_color'           => '#1e3a5f',
                    'accent_color'            => '#3b82f6',
                    'text_color'              => '#111827',
                    'border_color'            => '#e2e8f0',
                    'header_bg'               => '#1e3a5f',
                    'header_text_color'       => '#ffffff',
                    'logo_position'           => 'left',
                    'show_company_address'    => true,
                    'show_company_contact'    => true,
                    'header_text'             => 'ACCOUNT STATEMENT',
                    'footer_text'             => 'Please contact us immediately if you find any discrepancy in this statement.',
                    'show_page_numbers'       => true,
                    'watermark_text'          => '',
                    'show_item_code'          => false,
                    'show_description'        => true,
                    'show_discount_column'    => false,
                    'show_tax_column'         => false,
                    'show_subtotal'           => true,
                    'show_discount_total'     => false,
                    'show_tax_total'          => false,
                    'show_payment_terms'      => true,
                    'show_notes'              => false,
                    'show_terms'              => false,
                    'terms_text'              => '',
                    'show_bank_details'       => true,
                    'custom_header_fields'    => [],
                ],
            ],

        ];
    }
}
