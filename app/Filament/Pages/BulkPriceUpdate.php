<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductPricingRule;
use App\Models\ProductVariant;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class BulkPriceUpdate extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = null;
    protected static string|UnitEnum|null    $navigationGroup = 'Products';
    protected static ?string                 $navigationLabel = 'Bulk Price Update';
    protected static ?int                    $navigationSort  = 10;
    protected string                         $view            = 'filament.pages.bulk-price-update';

    // ── Product + rule state ──────────────────────────────────────────────────

    public ?int    $productId       = null;
    public string  $mechanism       = 'manual';
    public ?int    $baseBranchId    = null;
    public float   $baseWeight      = 50;
    public float   $branchPremium   = 10;
    public int     $weightRounding  = 5;
    public float   $weightPremium   = 0;

    // Base price for formula derivation
    public float $basePrice = 0;

    // Variant price rows: [ variantId => ['price' => '', 'effective_date' => ''] ]
    public array $variantPrices = [];

    // Derived read-only data (populated by loadProduct)
    public array  $variantRows   = [];   // [ ['id', 'label', 'weight', 'branch_id'] ]
    public array  $branchOptions = [];
    public string $productName   = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->branchOptions = Branch::orderBy('name')->pluck('name', 'id')->toArray();
    }

    // ── Product selection ─────────────────────────────────────────────────────

    public function updatedProductId(): void
    {
        if (! $this->productId) {
            $this->resetVariantState();
            return;
        }

        $this->loadProduct();
    }

    private function loadProduct(): void
    {
        $product = Product::find($this->productId);
        if (! $product) return;

        $this->productName = $product->name;

        // Load or create pricing rule
        $rule = ProductPricingRule::forProduct($this->productId);
        $this->mechanism      = $rule->mechanism;
        $this->baseBranchId   = $rule->base_branch_id;
        $this->baseWeight     = (float) ($rule->base_weight ?? 50);
        $this->branchPremium  = (float) $rule->branch_premium;
        $this->weightRounding = (int)   $rule->weight_rounding;
        $this->weightPremium  = (float) $rule->weight_premium;

        // Load variants
        $variants = ProductVariant::with('branch')
            ->where('product_id', $this->productId)
            ->where('is_active', true)
            ->orderBy('weight_kg')
            ->orderBy('grade')
            ->get();

        $this->variantRows   = [];
        $this->variantPrices = [];

        foreach ($variants as $v) {
            $this->variantRows[$v->id] = [
                'id'        => $v->id,
                'label'     => $v->name ?: $v->buildDisplayName(),
                'weight'    => (float) $v->weight_kg,
                'branch_id' => $v->branch_id,
                'branch'    => $v->branch?->name ?? '—',
                'grade'     => $v->grade,
            ];

            $this->variantPrices[$v->id] = [
                'price'          => (string) ($v->price ?? ''),
                'effective_date' => $v->effective_date?->toDateString() ?? now()->toDateString(),
            ];
        }

        // Set base price from the base variant (base weight + base branch)
        $baseVariant = collect($variants)->first(function ($v) {
            return (float) $v->weight_kg === $this->baseWeight
                && $v->branch_id === $this->baseBranchId;
        });
        $this->basePrice = $baseVariant ? (float) ($baseVariant->price ?? 0) : 0;
    }

    private function resetVariantState(): void
    {
        $this->variantRows   = [];
        $this->variantPrices = [];
        $this->productName   = '';
        $this->mechanism     = 'manual';
        $this->basePrice     = 0;
    }

    // ── Formula recalculation ─────────────────────────────────────────────────

    public function recalculate(): void
    {
        if ($this->mechanism !== 'formula' || ! $this->productId) return;

        $rule = new ProductPricingRule([
            'base_weight'     => $this->baseWeight,
            'branch_premium'  => $this->branchPremium,
            'weight_rounding' => $this->weightRounding,
            'weight_premium'  => $this->weightPremium,
        ]);

        foreach ($this->variantRows as $variantId => $row) {
            $isBaseBranch = $row['branch_id'] == $this->baseBranchId;
            $calculated   = $rule->calculatePrice($this->basePrice, $row['weight'], $isBaseBranch);

            $this->variantPrices[$variantId]['price'] = (string) $calculated;
        }

        Notification::make()->title('Prices recalculated. Review and save.')->info()->send();
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(): void
    {
        if (! $this->productId) {
            Notification::make()->title('Please select a product first.')->warning()->send();
            return;
        }

        DB::transaction(function () {
            // Save pricing rule
            ProductPricingRule::updateOrCreate(
                ['product_id' => $this->productId],
                [
                    'mechanism'       => $this->mechanism,
                    'base_branch_id'  => $this->baseBranchId ?: null,
                    'base_weight'     => $this->baseWeight,
                    'branch_premium'  => $this->branchPremium,
                    'weight_rounding' => $this->weightRounding,
                    'weight_premium'  => $this->weightPremium,
                ]
            );

            // Save variant prices
            foreach ($this->variantPrices as $variantId => $data) {
                $price = $data['price'] !== '' ? (float) $data['price'] : null;
                $date  = $data['effective_date'] ?: now()->toDateString();

                if ($price !== null) {
                    $variant = ProductVariant::find($variantId);
                    if (! $variant) continue;

                    $oldPrice = (float) ($variant->price ?? 0);

                    $variant->update([
                        'price'          => $price,
                        'effective_date' => $date,
                    ]);

                    // Log to price history if price changed
                    if (abs($price - $oldPrice) > 0.001) {
                        \App\Models\ProductPrice::create([
                            'product_id'     => $this->productId,
                            'variant_id'     => $variantId,
                            'branch_id'      => $variant->branch_id,
                            'price_type'     => 'sale',
                            'price'          => $price,
                            'effective_date' => $date,
                        ]);
                    }
                }
            }
        });

        Notification::make()->title('Prices saved successfully.')->success()->send();

        // Reload to reflect saved state
        $this->loadProduct();
    }

    // ── Helper for view ───────────────────────────────────────────────────────

    public function getProductOptions(): array
    {
        return Product::orderBy('name')->pluck('name', 'id')->toArray();
    }
}
