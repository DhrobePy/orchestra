<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTemplate extends Model
{
    protected $table = 'invoice_templates';

    protected $fillable = ['name', 'type', 'is_default', 'config'];

    protected $casts = [
        'config'     => 'array',
        'is_default' => 'boolean',
    ];

    /** Merge stored config with sensible defaults. */
    public function getEffectiveConfig(): array
    {
        return array_merge(self::defaults(), $this->config ?? []);
    }

    /** Fetch the default template for a given type, or null. */
    public static function defaultFor(string $type): ?self
    {
        return static::where('type', $type)->where('is_default', true)->first();
    }

    /** When setting as default, clear any other default for same type. */
    protected static function booted(): void
    {
        static::saving(function (self $tpl) {
            if ($tpl->is_default) {
                static::where('type', $tpl->type)
                    ->where('id', '!=', $tpl->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }

    public static function defaults(): array
    {
        return [
            // Layout
            'paper_size'   => 'A4',
            'orientation'  => 'portrait',
            'font_family'  => 'Arial, Helvetica, sans-serif',

            // Colors
            'primary_color'  => '#1e3a5f',
            'accent_color'   => '#f59e0b',
            'text_color'     => '#111827',
            'border_color'   => '#e5e7eb',
            'header_bg'      => '#1e3a5f',
            'header_text_color' => '#ffffff',

            // Branding
            'logo_position'          => 'left',   // left|center|right|hidden
            'show_company_address'   => true,
            'show_company_contact'   => true,

            // Header / Footer
            'header_text'   => '',
            'footer_text'   => 'Thank you for your business!',
            'show_page_numbers' => false,
            'watermark_text'    => '',

            // Line-item columns
            'show_item_code'        => false,
            'show_description'      => true,
            'show_discount_column'  => true,
            'show_tax_column'       => false,

            // Totals
            'show_subtotal'         => true,
            'show_discount_total'   => true,
            'show_tax_total'        => false,

            // Info blocks
            'show_payment_terms'    => true,
            'show_notes'            => true,
            'show_terms'            => false,
            'terms_text'            => '',
            'show_bank_details'     => true,

            // Custom header rows (label/value pairs)
            'custom_header_fields'  => [],
        ];
    }

    /** CSS variable block to inject into print views. */
    public function toCssVars(): string
    {
        $c = $this->getEffectiveConfig();
        return ":root {
            --tpl-primary:    {$c['primary_color']};
            --tpl-accent:     {$c['accent_color']};
            --tpl-text:       {$c['text_color']};
            --tpl-border:     {$c['border_color']};
            --tpl-header-bg:  {$c['header_bg']};
            --tpl-header-fg:  {$c['header_text_color']};
            --tpl-font:       {$c['font_family']};
        }";
    }
}
