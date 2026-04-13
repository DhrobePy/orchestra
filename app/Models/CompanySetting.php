<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $table = 'company_settings';

    protected $fillable = [
        'company_name',
        'tagline',
        'address',
        'city',
        'phone',
        'email',
        'website',
        'tax_id',
        'logo',
        'landing_page',
    ];

    protected $casts = [
        'landing_page' => 'array',
    ];

    /**
     * Always returns the single settings row (creates one if missing).
     */
    public static function get(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            ['company_name' => 'Orchestra ERP']
        );
    }

    /**
     * Returns the public URL for the logo (or null).
     */
    public function getLogoUrl(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    /**
     * Returns landing page settings with defaults merged in.
     */
    public function getLandingConfig(): array
    {
        $defaults = [
            'hero_title'         => $this->company_name ?? 'Orchestra ERP',
            'hero_subtitle'      => $this->tagline ?? 'Your complete business solution',
            'show_staff_login'   => true,
            'show_admin_login'   => true,
            'staff_login_label'  => 'Staff Login',
            'admin_login_label'  => 'Admin Login',
            'hero_style'         => 'glassmorphic',
            'nav_items'          => [],
        ];

        return array_merge($defaults, $this->landing_page ?? []);
    }
}
