<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia; // Remove LogsActivity for now

    protected $table = 'products';
    protected $guarded = [];

    protected $casts = [
        'price'      => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight_kg'  => 'decimal:2',
        'is_active'  => 'boolean',
    ];

    // If you want activity logging, you need to use the trait differently in v5
    // For now, let's comment out the activity log functionality
}