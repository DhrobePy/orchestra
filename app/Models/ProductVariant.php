<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $table = 'product_variants';
    protected $guarded = [];

    protected $casts = [
        'price'          => 'decimal:2',
        'stock'          => 'integer',
        'weight_kg'      => 'decimal:2',
        'effective_date' => 'date',
    ];

    // ── Boot: auto-generate display name on save ──────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $variant) {
            $variant->name = $variant->buildDisplayName();
        });
    }

    /**
     * Build the canonical display name from components.
     * Format: "{product} ({weight}kg) Grade {grade} ({branch})"
     * Example: "1Hati Moida (50kg) Grade 1 (Demra Factory)"
     */
    public function buildDisplayName(): string
    {
        $product    = $this->product?->name ?? Product::find($this->product_id)?->name ?? '';
        $branchName = $this->branch?->name  ?? Branch::find($this->branch_id)?->name  ?? '';

        $parts = array_filter([
            $product,
            $this->weight_kg  ? '(' . (int) $this->weight_kg . 'kg)'   : null,
            $this->grade      ? 'Grade ' . strtoupper($this->grade)     : null,
            $branchName       ? '(' . $branchName . ')'                 : null,
        ]);

        return implode(' ', $parts) ?: ($this->name ?? '');
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'variant_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
