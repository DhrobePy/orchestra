<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPricingRule extends Model
{
    protected $table = 'product_pricing_rules';

    protected $fillable = [
        'product_id',
        'mechanism',       // 'manual' | 'formula'
        'base_branch_id',
        'base_weight',
        'branch_premium',
        'weight_rounding',
        'weight_premium',
    ];

    protected $casts = [
        'base_weight'     => 'decimal:2',
        'branch_premium'  => 'decimal:2',
        'weight_rounding' => 'integer',
        'weight_premium'  => 'decimal:2',
    ];

    const MECHANISM_MANUAL  = 'manual';
    const MECHANISM_FORMULA = 'formula';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function baseBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'base_branch_id');
    }

    /**
     * Get or create the pricing rule for a product.
     */
    public static function forProduct(int $productId): self
    {
        return static::firstOrCreate(
            ['product_id' => $productId],
            [
                'mechanism'       => self::MECHANISM_MANUAL,
                'branch_premium'  => 10,
                'weight_rounding' => 5,
                'weight_premium'  => 0,
            ]
        );
    }

    /**
     * Calculate the derived price for a variant given a base price.
     *
     * @param  float  $basePrice      Base price (base weight + base branch)
     * @param  float  $variantWeight  The target variant weight
     * @param  bool   $isBaseBranch   Whether this variant is on the base branch
     */
    public function calculatePrice(float $basePrice, float $variantWeight, bool $isBaseBranch): float
    {
        $baseWeight = (float) ($this->base_weight ?? $variantWeight);

        // Step 1: weight scaling
        if ($baseWeight > 0 && $variantWeight !== $baseWeight) {
            $proportional = ($basePrice / $baseWeight) * $variantWeight;
            // Round to nearest weight_rounding
            $r = max(1, (int) $this->weight_rounding);
            $rounded = round($proportional / $r) * $r;
            $price = $rounded + (float) $this->weight_premium;
        } else {
            $price = $basePrice;
        }

        // Step 2: branch premium
        if (! $isBaseBranch) {
            $price += (float) $this->branch_premium;
        }

        return round($price, 2);
    }
}
