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
}
